<?php

namespace App\Passport;

use App\Models\User;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Bridge\AccessTokenRepository as BaseAccessTokenRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;

class AccessTokenRepository extends BaseAccessTokenRepository
{
    public function __construct(Dispatcher $events)
    {
        parent::__construct($events);
    }

    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        ?string $userIdentifier = null,
    ): AccessTokenEntityInterface {
        /** @var AccessToken $token */
        $token = new Passport::$accessTokenEntity($userIdentifier, $scopes, $clientEntity);

        if ($userIdentifier !== null) {
            $token->setProfile(
                User::query()->with('roles')->find($userIdentifier)
            );
        }

        return $token;
    }
}
