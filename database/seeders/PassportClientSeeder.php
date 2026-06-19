<?php

namespace Database\Seeders;

use App\Support\Passport\PasswordGrantClientCredentials;
use Illuminate\Database\Seeder;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

class PassportClientSeeder extends Seeder
{
    public function __construct(private readonly ClientRepository $clients) {}

    /**
     * Ensure a confidential password-grant client exists and its plaintext
     * credentials are persisted for AuthService (Passport hashes secrets at rest).
     */
    public function run(): void
    {
        $client = Passport::client()
            ->newQuery()
            ->where('revoked', false)
            ->get()
            ->first(fn (Client $client): bool => $client->hasGrantType('password') && $client->confidential());

        if ($client === null) {
            $client = $this->clients->createPasswordGrantClient(
                name: config('app.name').' Password Grant Client',
                provider: config('auth.guards.api.provider'),
                confidential: true,
            );

            PasswordGrantClientCredentials::persist($client->id, (string) $client->plainSecret);

            return;
        }

        $hasEnvCredentials = config('services.passport.password_client_id')
            && config('services.passport.password_client_secret');

        if (! $hasEnvCredentials && ! PasswordGrantClientCredentials::exists()) {
            $this->clients->regenerateSecret($client);
            $client->refresh();

            PasswordGrantClientCredentials::persist($client->id, (string) $client->plainSecret);
        }
    }
}
