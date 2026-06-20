<?php

namespace App\Support\Passport;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Resolves the OAuth2 password-grant client credentials.
 *
 * Passport hashes client secrets at rest, so the plaintext secret is only
 * available at creation time. We persist it once to private storage (or
 * inject via env in production) and read from there at runtime.
 */
final class PasswordGrantClientCredentials
{
    private const STORAGE_PATH = 'private/oauth/password-grant-client.json';

    public function clientId(): string
    {
        if ($id = config('services.passport.password_client_id')) {
            return (string) $id;
        }

        return (string) ($this->fromStorage()['client_id']
            ?? throw new RuntimeException('Password grant client ID is not configured.'));
    }

    public function clientSecret(): string
    {
        if ($secret = config('services.passport.password_client_secret')) {
            return (string) $secret;
        }

        return (string) ($this->fromStorage()['client_secret']
            ?? throw new RuntimeException(
                'Password grant client secret is not configured. '
                .'Set PASSPORT_PASSWORD_CLIENT_SECRET or re-run PassportClientSeeder.'
            ));
    }

    /**
     * Persist plaintext credentials (called once when the client is created).
     */
    public static function persist(string $clientId, string $plainSecret): void
    {
        Storage::disk('local')->put(self::STORAGE_PATH, json_encode([
            'client_id' => $clientId,
            'client_secret' => $plainSecret,
        ], JSON_THROW_ON_ERROR));
    }

    public static function exists(): bool
    {
        return Storage::disk('local')->exists(self::STORAGE_PATH);
    }

    /**
     * @return array{client_id?: string, client_secret?: string}
     */
    private function fromStorage(): array
    {
        if (! Storage::disk('local')->exists(self::STORAGE_PATH)) {
            return [];
        }

        /** @var array{client_id?: string, client_secret?: string} */
        return json_decode(
            Storage::disk('local')->get(self::STORAGE_PATH),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
