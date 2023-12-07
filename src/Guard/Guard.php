<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bolivir\LaravelDoctrineSanctum\Guard;

use Bolivir\LaravelDoctrineSanctum\Contracts\ISanctumUser;
use Bolivir\LaravelDoctrineSanctum\Repository\IAccessTokenRepository;
use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthenticationFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class Guard
{
    public function __construct(private AuthenticationFactory $authenticationFactory, private IAccessTokenRepository $accessTokenRepository, private ?int $expiration = null, private ?string $provider = null)
    {
    }

    /**
     * Retrieve the authenticated user for the request.
     *
     * @returns ISanctumUser|Authenticatable
     */
    public function __invoke(Request $request)
    {
        foreach (Arr::wrap(config('sanctum.guard', 'web')) as $guard) {
            if (($user = $this->authenticationFactory->guard($guard)->user()) !== null) {
                return $this->supportsTokens($user)
                    ? $this->accessTokenRepository->createTransientToken($user)
                    : $user;
            }
        }

        if ($token = $request->bearerToken()) {
            $accessToken = $this->accessTokenRepository->findToken($token);
            if (
                !$accessToken
                || !$this->hasValidProvider($accessToken->owner())
                || (
                    $this->expiration
                    && Carbon::instance($accessToken->createdAt())->lte(now()->subMinutes($this->expiration))
                )
            ) {
                return null;
            }

            if ($this->supportsTokens($accessToken->owner())) {
                $accessToken->changeLastUsedAt(now());

                return $this->accessTokenRepository->updateAccessToken($accessToken);
            }
        }

        return null;
    }

    protected function supportsTokens(mixed $tokenable = null): bool
    {
        return $tokenable && $tokenable instanceof ISanctumUser;
    }

    protected function hasValidProvider(mixed $owner): bool
    {
        if (null === $this->provider) {
            return true;
        }

        return $owner instanceof ISanctumUser;
    }
}
