<?php

namespace Bolivir\LaravelDoctrineSanctum\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Sanctum\Contracts\HasAbilities;

interface ISanctumUser extends Authenticatable
{
    public function tokenCan(string $ability): bool;

    public function currentAccessToken();

    /** @param HasAbilities|IAccessToken */
    public function withAccessToken($accessToken): void;

    public function findToken(string $token): ?IAccessToken;

    public function revokeToken(IAccessToken $token): void;

    public function revokeAllAccessTokens(): void;
}
