#!/bin/sh
set -eu
script_dir=$(dirname "$0")
php "$script_dir/php-runtime-guard.php"
exec "$@"
