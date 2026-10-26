"""Dependency-free executable contracts for the PHP security source patch."""

import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile
import textwrap
import unittest


ROOT = Path(__file__).resolve().parents[1]
PHP = os.environ.get("USS_TEST_PHP_BIN", "php")
PHP_INI = os.environ.get("USS_TEST_PHP_INI")


def php(code):
    args = [PHP]
    if PHP_INI:
        args.extend(["-c", PHP_INI])
    else:
        args.append("-n")
    return subprocess.run(args + ["-r", code], cwd=ROOT, capture_output=True, text=True, check=False)


class SourceSecurityTest(unittest.TestCase):
    def test_pr_workflow_is_read_only_and_checks_actual_php_image_and_application(self):
        workflow = ROOT / ".github/workflows/php-security-pr.yml"
        self.assertTrue(workflow.is_file(), "missing isolated pull_request validation workflow")
        text = workflow.read_text(encoding="utf-8")
        self.assertIn("\non:\n  pull_request:\n", text)
        self.assertIn("permissions:\n  contents: read", text)
        self.assertIn("cancel-in-progress: true", text)
        self.assertIn("timeout-minutes: 30", text)
        self.assertIn("runs-on: ubuntu-latest", text)
        self.assertNotRegex(text, r"(?i)secrets\.|workflow_run:|workflow_dispatch:|docker login|docker push|ssh|continue-on-error")
        self.assertNotIn("--ignore-platform-req", text)
        self.assertIn("set -euo pipefail", text)
        self.assertIn("python3 -m unittest discover -s tests -p 'test_source_security.py'", text)
        self.assertIn("composer install --no-interaction", text)
        self.assertIn("php vendor/bin/phpunit --colors=never", text)
        self.assertNotIn("php artisan test", text)
        self.assertIn("docker build --pull --no-cache", text)
        self.assertIn("--network none --read-only", text)
        self.assertIn("php-fpm", text)
        self.assertIn("session.upload_progress.enabled", text)
        self.assertIn("8.2.35-cli-alpine3.23@sha256:198ed9e076a5c6ad8c17b64c3ff874c276a5e86527a9888f07eee837bc862040", text)
        steps = re.split(r"(?m)^      - name: ", text)[1:]
        names = [x.partition("\n")[0] for x in steps]
        setup_index = names.index("Setup PHP")
        self.assertEqual(names[setup_index + 1], "Verify installed PHP patch")
        setup = steps[setup_index]
        self.assertIn("uses: shivammathur/setup-php@d52fc211a0436f8839a587b3b5b8efddab4744ea", setup)
        self.assertRegex(setup, r"(?m)^          update: true$")
        guard = steps[setup_index + 1]
        self.assertIn("PHP_VERSION_ID !== 80427", guard)
        self.assertNotIn("if:", guard)

    def test_pr_workflow_requires_a_failing_closed_locked_audit_before_install(self):
        workflow = (ROOT / ".github/workflows/php-security-pr.yml").read_text(encoding="utf-8")
        steps = re.split(r"(?m)^      - name: ", workflow)[1:]
        names = [step.partition("\n")[0] for step in steps]
        audit_name = "Locked production dependency security audit"
        self.assertIn(audit_name, names)
        audit_index = names.index(audit_name)
        self.assertLess(audit_index, names.index("Locked dependencies and application tests"))
        self.assertLess(audit_index, names.index("Build untrusted PR image locally only"))
        step = steps[audit_index]
        self.assertIn("        shell: bash\n", step)
        self.assertNotRegex(step, r"(?m)^        (?:if:|continue-on-error:)")
        self.assertIn("        run: |\n", step)
        script = textwrap.dedent(step.split("        run: |\n", 1)[1]).strip() + "\n"
        self.assertEqual(script.splitlines()[0], "set -euo pipefail")
        self.assertEqual(script.splitlines()[1:], ["composer audit --locked --no-interaction"])

        for mode, expected_status in (("success", 0), ("advisories", 37), ("deceptive", 23)):
            with self.subTest(mode=mode):
                control = """composer() {
                    [[ "$*" == 'audit --locked --no-interaction' ]] || return 44
                    case "$AUDIT_CASE" in
                        success) printf 'No security vulnerability advisories found.\\n'; return 0 ;;
                        advisories) printf 'Found advisories.\\n'; return 37 ;;
                        deceptive) printf 'No security vulnerability advisories found.\\n'; return 23 ;;
                    esac
                    return 45
                }
                """
                env = os.environ.copy()
                env["AUDIT_CASE"] = mode
                result = subprocess.run(
                    [shutil.which("bash") or "bash", "-c", control + script + "\nprintf 'DOWNSTREAM_REACHED\\n'\n"],
                    cwd=ROOT, env=env, capture_output=True, text=True, check=False,
                )
                self.assertEqual(result.returncode, expected_status, result.stderr)
                self.assertEqual("DOWNSTREAM_REACHED" in result.stdout, mode == "success")

    def test_docker_context_allowlists_only_application_source_and_creates_writable_dirs(self):
        ignore = ROOT / ".dockerignore"
        self.assertTrue(ignore.is_file(), "missing fail-closed build context allowlist")
        lines = [x.strip() for x in ignore.read_text(encoding="utf-8").splitlines() if x.strip() and not x.startswith("#")]
        self.assertEqual(lines[0], "*")
        self.assertTrue(all("*" not in x for x in lines[1:] if x.startswith("!")))
        selected = {x[1:] for x in lines[1:] if x.startswith("!")}
        tracked = subprocess.check_output(["git", "ls-files"], cwd=ROOT, text=True).splitlines()
        source_roots = ("app/", "bootstrap/", "config/", "database/", "docker/", "public/", "resources/", "routes/")
        required = {p for p in tracked if p in ("artisan", "composer.json", "composer.lock") or (p.startswith(source_roots) and not p.endswith(".gitignore"))}
        required.update({"docker/php-runtime-guard.php", "docker/php-entrypoint.sh", "docker/php-security.ini"})
        self.assertEqual({p for p in selected if not p.endswith("/")}, required)
        for path in required:
            self.assertNotIn(".env", path)
            self.assertNotIn("secret", path.lower())
            parts = path.split("/")
            for n in range(1, len(parts)):
                self.assertIn("/".join(parts[:n]) + "/", selected)
        dockerfile = (ROOT / "Dockerfile").read_text(encoding="utf-8")
        self.assertLess(dockerfile.index("RUN mkdir -p storage/"), dockerfile.index("RUN composer install"))
        self.assertIn("bootstrap/cache", dockerfile)

    def test_docker_context_reopens_only_required_directories_with_child_denies(self):
        # An unpaired !directory/ also includes every unlisted descendant in Docker.
        tracked = subprocess.check_output(["git", "ls-files"], cwd=ROOT, text=True).splitlines()
        source_roots = ("app/", "bootstrap/", "config/", "database/", "docker/", "public/", "resources/", "routes/")
        required = {p for p in tracked if p in ("artisan", "composer.json", "composer.lock") or (p.startswith(source_roots) and not p.endswith(".gitignore"))}
        required.update({"docker/php-runtime-guard.php", "docker/php-entrypoint.sh", "docker/php-security.ini"})
        directories = set()
        for path in required:
            parts = path.split("/")
            directories.update("/".join(parts[:n]) for n in range(1, len(parts)))
        expected = ["*"]
        for directory in sorted(directories, key=lambda p: (p.count("/"), p)):
            expected.extend((f"!{directory}/", f"{directory}/*"))
        expected.extend(f"!{path}" for path in sorted(required))
        ignore = ROOT / ".dockerignore"
        lines = [x.strip() for x in ignore.read_text(encoding="utf-8").splitlines() if x.strip() and not x.startswith("#")]
        self.assertEqual(lines, expected, "only reviewed leaves may be included after each directory's child deny")

    def test_native_session_upload_progress_is_disabled_in_php_ini(self):
        ini = ROOT / "docker/php-security.ini"
        self.assertTrue(ini.is_file(), "missing early PHP INI security setting")
        self.assertEqual(ini.read_text(encoding="utf-8").strip(), "session.upload_progress.enabled=0")
        dockerfile = (ROOT / "Dockerfile").read_text(encoding="utf-8")
        self.assertIn("COPY docker/php-security.ini /usr/local/etc/php/conf.d/zz-uss-security.ini", dockerfile)
        result = subprocess.run([PHP, "-c", str(ini), "-r", "echo ini_get('session.upload_progress.enabled');"], cwd=ROOT, capture_output=True, text=True, check=False)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout, "0")

    def test_dashboard_feature_test_blocks_external_http_before_request(self):
        test = (ROOT / "tests/Feature/ExampleTest.php").read_text(encoding="utf-8")
        self.assertIn("Http::preventStrayRequests();", test)
        self.assertIn("Http::fake(", test)
        self.assertLess(test.index("Http::preventStrayRequests();"), test.index("$this->get('/')"))
        self.assertLess(test.index("Http::fake("), test.index("$this->get('/')"))
        self.assertIn("Http::assertSentCount(6);", test)

    def test_composer_manifest_and_lock_restrict_unpatched_minors(self):
        manifest = json.loads((ROOT / "composer.json").read_text(encoding="utf-8"))
        lock = json.loads((ROOT / "composer.lock").read_text(encoding="utf-8"))
        self.assertEqual(next(p for p in lock["packages"] if p["name"] == "symfony/console")["require"]["php"], ">=8.4")
        constraint = ">=8.4.27 <8.5 || >=8.5.12 <8.6"
        self.assertEqual(manifest["require"]["php"], constraint)
        self.assertEqual(manifest["config"]["platform"]["php"], "8.4.27")
        self.assertEqual(lock["platform"]["php"], constraint)
        self.assertEqual(lock["platform-overrides"]["php"], "8.4.27")

    def test_laravel_security_floor_and_locked_patch(self):
        manifest = json.loads((ROOT / "composer.json").read_text(encoding="utf-8"))
        lock = json.loads((ROOT / "composer.lock").read_text(encoding="utf-8"))
        packages = {package["name"]: package for package in lock["packages"]}
        self.assertEqual(manifest["require"]["laravel/framework"], "^13.30.0")
        self.assertEqual(packages["laravel/framework"]["version"].lstrip("v"), "13.30.0")

    def test_guzzle_locked_security_patches_and_required_promises(self):
        lock = json.loads((ROOT / "composer.lock").read_text(encoding="utf-8"))
        packages = {package["name"]: package for package in lock["packages"]}
        self.assertEqual(packages["guzzlehttp/guzzle"]["version"], "7.15.2")
        self.assertEqual(packages["guzzlehttp/psr7"]["version"], "2.13.0")
        self.assertEqual(packages["guzzlehttp/promises"]["version"], "2.5.1")

    def test_league_locked_security_patches(self):
        lock = json.loads((ROOT / "composer.lock").read_text(encoding="utf-8"))
        packages = {package["name"]: package for package in lock["packages"]}
        self.assertEqual(packages["league/commonmark"]["version"], "2.10.2")
        self.assertEqual(packages["league/flysystem"]["version"], "3.35.3")

    def test_symfony_locked_security_patches(self):
        lock = json.loads((ROOT / "composer.lock").read_text(encoding="utf-8"))
        packages = {package["name"]: package for package in lock["packages"]}
        expected = {
            "symfony/http-foundation": "8.1.9",
            "symfony/http-kernel": "8.0.12",
            "symfony/mailer": "8.0.12",
            "symfony/mime": "8.0.12",
            "symfony/polyfill-intl-idn": "1.38.1",
            "symfony/routing": "8.0.13",
        }
        for name, version in expected.items():
            with self.subTest(package=name):
                self.assertEqual(packages[name]["version"].lstrip("v"), version)

    def test_container_startup_checks_php_before_running_command(self):
        dockerfile = (ROOT / "Dockerfile").read_text(encoding="utf-8")
        self.assertIn("COPY docker/php-entrypoint.sh /usr/local/bin/uss-entrypoint", dockerfile)
        self.assertIn('ENTRYPOINT ["/usr/local/bin/uss-entrypoint"]', dockerfile)
        entry = (ROOT / "docker/php-entrypoint.sh").read_text(encoding="utf-8")
        self.assertLess(entry.index('php "$script_dir/php-runtime-guard.php"'), entry.index('exec "$@"'))
        env = os.environ.copy()
        env["PATH"] = str(Path(PHP).resolve().parent) + os.pathsep + env.get("PATH", "")
        result = subprocess.run([shutil.which("sh") or "sh", str(ROOT / "docker/php-entrypoint.sh"), PHP, "-r", "echo 'SAFE';"], cwd=ROOT, env=env, capture_output=True, text=True, check=False)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout, "SAFE")

    def test_container_startup_stops_on_guard_producer_error(self):
        with tempfile.TemporaryDirectory(dir=os.environ.get("TMPDIR")) as temp:
            shim = Path(temp) / "php"
            shim.write_text("#!/bin/sh\nprintf 'GUARD_ERROR\\n' >&2\nexit 37\n", encoding="utf-8")
            shim.chmod(0o755)
            env = os.environ.copy()
            env["PATH"] = temp + os.pathsep + env.get("PATH", "")
            result = subprocess.run([shutil.which("sh") or "sh", str(ROOT / "docker/php-entrypoint.sh"), shutil.which("sh") or "sh", "-c", "printf REACHED"], cwd=ROOT, env=env, capture_output=True, text=True, check=False)
            self.assertEqual(result.returncode, 37, result.stderr)
            self.assertIn("GUARD_ERROR", result.stderr)
            self.assertNotIn("REACHED", result.stdout)

    def test_image_executes_only_pinned_fixed_php_and_composer_is_copy_only(self):
        dockerfile = (ROOT / "Dockerfile").read_text(encoding="utf-8")
        runtime = "php:8.4.27-fpm-alpine3.23@sha256:9eb263ac0fa59dfbef3dbaeebeb5f2319e212fc28c168a8c416050f77705b5b4"
        donor = "composer:2@sha256:b6dcd72ca98fa19d0e5b8476de179859704891fad06dd9e0b96833d4c430a5fc"
        self.assertEqual([line for line in dockerfile.splitlines() if line.startswith("FROM ")], ["FROM " + runtime])
        self.assertIn("COPY --from=" + donor + " /usr/bin/composer /usr/bin/composer", dockerfile)
        self.assertLess(dockerfile.index("RUN php docker/php-runtime-guard.php"), dockerfile.index("RUN composer install"))

    def test_direct_cli_and_web_callers_guard_before_application_loading(self):
        for relative, include, later in [
            ("artisan", "require __DIR__.'/docker/php-runtime-guard.php';", "vendor/autoload.php"),
            ("public/index.php", "require __DIR__.'/../docker/php-runtime-guard.php';", "storage/framework/maintenance.php"),
        ]:
            with self.subTest(caller=relative):
                source = (ROOT / relative).read_text(encoding="utf-8")
                self.assertIn(include, source)
                self.assertLess(source.index(include), source.index(later))

    def test_both_real_callers_abort_before_missing_vendor_on_rejected_version(self):
        with tempfile.TemporaryDirectory(dir=os.environ.get("TMPDIR")) as temp:
            fixture = Path(temp)
            (fixture / "docker").mkdir()
            (fixture / "public").mkdir()
            guard = (ROOT / "docker/php-runtime-guard.php").read_text(encoding="utf-8")
            self.assertIn("uss_php_security_enforce(PHP_VERSION_ID);", guard)
            (fixture / "docker/php-runtime-guard.php").write_text(guard.replace("uss_php_security_enforce(PHP_VERSION_ID);", "uss_php_security_enforce(80426);"), encoding="utf-8")
            for relative in ("artisan", "public/index.php"):
                shutil.copyfile(ROOT / relative, fixture / relative)
                result = subprocess.run([PHP, "-n", str(fixture / relative)], cwd=fixture, capture_output=True, text=True, check=False)
                self.assertEqual(result.returncode, 78, (relative, result.stderr))
                self.assertIn("Unsupported PHP security patch", result.stderr)
                self.assertNotIn("autoload", result.stderr)

    def test_guard_refuses_cli_before_later_application_code(self):
        result = php("require 'docker/php-runtime-guard.php'; uss_php_security_enforce(80426); echo 'REACHED';")
        self.assertEqual(result.returncode, 78, result.stderr)
        self.assertIn("Unsupported PHP security patch", result.stderr)
        self.assertNotIn("REACHED", result.stdout)

    def test_guard_accepts_only_fixed_supported_minor_patches(self):
        code = """require 'docker/php-runtime-guard.php';
            foreach ([80335,80336,80426,80427,80511,80512,80600,80235] as $v) {
                echo uss_php_security_supported($v) ? '1' : '0';
            }"""
        result = php(code)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout, "00010100")


if __name__ == "__main__":
    unittest.main()
