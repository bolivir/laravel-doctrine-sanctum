<?php

namespace Bolivir\LaravelDoctrineSanctum\Repository;

use Bolivir\LaravelDoctrineSanctum\Contracts\IAccessToken;
use Bolivir\LaravelDoctrineSanctum\Contracts\ISanctumUser;
use Bolivir\LaravelDoctrineSanctum\NewAccessToken;
use Illuminate\Auth\Authenticatable;

interface IAccessTokenRepository
{
    public function createToken(ISanctumUser $user, string $name, array $abilities = ['*']): NewAccessToken;

    public function findToken(string $token): ?IAccessToken;

    /** @param Authenticatable|ISanctumUser */
    public function createTransientToken($user): ?ISanctumUser;

    /** @return Authenticatable|ISanctumUser */
    public function updateAccessToken(IAccessToken $token);

    public function save(IAccessToken $token): void;
}
