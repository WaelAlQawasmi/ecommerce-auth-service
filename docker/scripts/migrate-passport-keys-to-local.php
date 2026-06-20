<?php

/**
 * One-off helper: move multi-line Passport PEM keys from .env.docker
 * into gitignored .env.docker.local as Docker-safe single-line values.
 */

$root = dirname(__DIR__, 2);
$source = $root.'/.env.docker';
$target = $root.'/.env.docker.local';

$content = file_get_contents($source);
if ($content === false) {
    fwrite(STDERR, "Cannot read {$source}\n");
    exit(1);
}

if (! preg_match('/PASSPORT_PRIVATE_KEY="(-----BEGIN PRIVATE KEY-----.*?-----END PRIVATE KEY-----)"/s', $content, $privateMatch)) {
    fwrite(STDERR, "No multi-line PASSPORT_PRIVATE_KEY found in .env.docker\n");
    exit(1);
}

if (! preg_match('/PASSPORT_PUBLIC_KEY="(-----BEGIN PUBLIC KEY-----.*?-----END PUBLIC KEY-----)"/s', $content, $publicMatch)) {
    fwrite(STDERR, "No multi-line PASSPORT_PUBLIC_KEY found in .env.docker\n");
    exit(1);
}

$toSingleLine = static fn (string $pem): string => str_replace(["\r\n", "\n", "\r"], '\\n', $pem);

$private = $toSingleLine($privateMatch[1]);
$public = $toSingleLine($publicMatch[1]);

$local = <<<'HEADER'
# .env.docker.local — local secrets (gitignored). Overrides .env.docker.
# Passport keys: single-line PEM with \n escapes (Docker env_file safe).

HEADER;

$local .= 'PASSPORT_PRIVATE_KEY="'.$private.'"'.PHP_EOL;
$local .= 'PASSPORT_PUBLIC_KEY="'.$public.'"'.PHP_EOL;

if (file_put_contents($target, $local) === false) {
    fwrite(STDERR, "Cannot write {$target}\n");
    exit(1);
}

echo "Wrote {$target}\n";
