<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bolivir\LaravelDoctrineSanctum\Repository;

use Bolivir\LaravelDoctrineSanctum\Contracts\IAccessToken;
use Bolivir\LaravelDoctrineSanctum\Contracts\ISanctumUser;
use Bolivir\LaravelDoctrineSanctum\NewAccessToken;
use Illuminate\Contracts\Auth\Authenticatable;

interface IAccessTokenRepository
{
    public function createToken(ISanctumUser $user, string $name, array $abilities = ['*']): NewAccessToken;

    public function findToken(string $token): ?IAccessToken;

    /** @return IAccessToken[]|null */
    public function findUnusedTokens(): ?array;

    /** @param Authenticatable|ISanctumUser $user */
    public function createTransientToken($user): ?ISanctumUser;

    /** @return Authenticatable|ISanctumUser */
    public function updateAccessToken(IAccessToken $token);

    public function remove(IAccessToken $token): void;

    public function save(IAccessToken $token): void;
}
