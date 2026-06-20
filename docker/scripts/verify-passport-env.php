<?php

$json = shell_exec('docker inspect ecommerce-auth-service-app-1 -f "{{json .Config.Env}}"');
$env = json_decode($json, true) ?: [];

foreach ($env as $entry) {
    if (! str_starts_with($entry, 'PASSPORT_')) {
        continue;
    }

    [$name, $value] = array_pad(explode('=', $entry, 2), 2, '');
    $ok = str_contains($value, 'BEGIN') && str_contains($value, 'END') && strlen($value) > 500;
    echo $name.': '.($ok ? 'ok' : 'invalid').' (len='.strlen($value).')'.PHP_EOL;
}
