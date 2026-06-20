<?php

return [

    'guard' => 'web',

    'middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys (centralized for multi-instance / cloud)
    |--------------------------------------------------------------------------
    |
    | Production: inject PASSPORT_PRIVATE_KEY and PASSPORT_PUBLIC_KEY from your
    | secrets manager (AWS Secrets Manager, Vault, K8s secrets) into every
    | replica — app, queue, and scheduler must share the same key pair.
    |
    | When both env vars are set, Passport ignores storage/oauth-*.key files.
    | When unset, keys are read from storage/ (generated once by the entrypoint
    | on first boot, or copied from PASSPORT_*_KEY_FILE mounts).
    |
    */

    'private_key' => env('PASSPORT_PRIVATE_KEY'),

    'public_key' => env('PASSPORT_PUBLIC_KEY'),

    'connection' => env('PASSPORT_CONNECTION'),

];
