<?php

// The upstream fixes are per-minor; a later unpatched minor is not safe by implication.
function uss_php_security_supported(int $versionId): bool
{
    return match (intdiv($versionId, 100)) {
        804 => $versionId >= 80427,
        805 => $versionId >= 80512,
        default => false,
    };
}

function uss_php_security_enforce(int $versionId): void
{
    if (uss_php_security_supported($versionId)) {
        return;
    }

    $message = 'Unsupported PHP security patch; require 8.4.27+ or 8.5.12+ in its minor series.';

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message.PHP_EOL);
    } else {
        http_response_code(503);
        error_log($message);
        echo 'Service unavailable';
    }

    exit(78);
}

uss_php_security_enforce(PHP_VERSION_ID);
