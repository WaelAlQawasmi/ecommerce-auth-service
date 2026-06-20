<?php

namespace App\Passport;

use App\Models\User;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Laravel\Passport\Bridge\AccessToken as PassportAccessToken;
use League\OAuth2\Server\CryptKeyInterface;
use RuntimeException;

class AccessToken extends PassportAccessToken
{
    private CryptKeyInterface $signingKey;

    private ?User $profile = null;

    public function setProfile(?User $user): void
    {
        $this->profile = $user;
    }

    public function setPrivateKey(CryptKeyInterface $privateKey): void
    {
        parent::setPrivateKey($privateKey);

        $this->signingKey = $privateKey;
    }

    public function toString(): string
    {
        return $this->buildJwt()->toString();
    }

    private function buildJwt(): Token
    {
        $privateKeyContents = $this->signingKey->getKeyContents();

        if ($privateKeyContents === '') {
            throw new RuntimeException('Private key is empty');
        }

        $jwtConfiguration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($privateKeyContents, $this->signingKey->getPassPhrase() ?? ''),
            InMemory::plainText('empty', 'empty'),
        );

        $builder = $jwtConfiguration->builder()
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt(new DateTimeImmutable())
            ->canOnlyBeUsedAfter(new DateTimeImmutable())
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo($this->subjectIdentifier())
            ->withClaim('scopes', $this->getScopes());

        $builder = $this->appendUserClaims($builder);

        return $builder->getToken(
            $jwtConfiguration->signer(),
            $jwtConfiguration->signingKey(),
        );
    }

    /**
     * @param  \Lcobucci\JWT\Token\Builder  $builder
     * @return \Lcobucci\JWT\Token\Builder
     */
    private function appendUserClaims($builder)
    {
        $user = $this->profile;

        if ($user === null && $this->getUserIdentifier() !== null) {
            $user = User::query()->with('roles')->find($this->getUserIdentifier());
        }

        if ($user === null) {
            return $builder;
        }

        return $builder
            ->withClaim('id', $user->getKey())
            ->withClaim('name', $user->name)
            ->withClaim('email', $user->email)
            ->withClaim('role', $user->roles->pluck('slug')->values()->all());
    }

    /**
     * @return non-empty-string
     */
    private function subjectIdentifier(): string
    {
        return $this->getUserIdentifier() ?? $this->getClient()->getIdentifier();
    }
}
