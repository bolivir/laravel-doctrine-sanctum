<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bolivir\LaravelDoctrineSanctum\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Sanctum\Contracts\HasAbilities;

interface ISanctumUser extends Authenticatable
{
    public function tokenCan(string $ability): bool;

    public function currentAccessToken(): ?IAccessToken;

    /** @param HasAbilities|IAccessToken $accessToken*/
    public function withAccessToken($accessToken): void;

    public function findToken(string $token): ?IAccessToken;

    public function revokeToken(IAccessToken $token): void;

    public function revokeAllAccessTokens(): void;
}
