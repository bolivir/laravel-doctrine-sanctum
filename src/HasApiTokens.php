<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bolivir\LaravelDoctrineSanctum;

use Bolivir\LaravelDoctrineSanctum\Contracts\IAccessToken;
use Doctrine\Common\Collections\Collection;

trait HasApiTokens
{
    protected IAccessToken $accessToken;

    /** @var Collection<IAccessToken> */
    protected Collection $accessTokens;

    public function tokenCan(string $ability): bool
    {
        return $this->accessToken ? $this->accessToken->can($ability) : false;
    }

    public function currentAccessToken(): IAccessToken
    {
        return $this->accessToken;
    }

    /** {@inheritDoc} */
    public function withAccessToken($accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function revokeAllAccessTokens(): void
    {
        $this->accessTokens->clear();
    }

    public function revokeToken(IAccessToken $token): void
    {
        $this->accessTokens->removeElement($token);
    }

    public function findToken(string $token): ?IAccessToken
    {
        if (false === strpos($token, '|')) {
            return $this->accessTokens->filter(function (IAccessToken $accessToken) use ($token) {
                return $accessToken->token() === hash('sha256', $token);
            })->first();
        }

        [$id, $token] = explode('|', $token, 2);

        return $this->accessTokens->filter(function (IAccessToken $accessToken) use ($token) {
            return hash_equals($accessToken->token(), hash('sha256', $token)) ? $accessToken : null;
        })->first();
    }
}
