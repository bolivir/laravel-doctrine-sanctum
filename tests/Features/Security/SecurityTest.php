<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Bolivir\LaravelDoctrineSanctum\Features\Security;

use Bolivir\LaravelDoctrineSanctum\Repository\IAccessTokenRepository;
use Tests\Bolivir\LaravelDoctrineSanctum\TestCase;

class SecurityTest extends TestCase
{
    public function testLoginWithExpiredTokenShows302Status()
    {
        /** @var IAccessTokenRepository $accessTokenRepository */
        $user = $this->createUser();
        $accessTokenRepository = app()->get(IAccessTokenRepository::class);

        $token = $accessTokenRepository->createToken($user, 'phpunit');
        $currentUserToken = $token->accessToken;
        $currentUserToken->setCreatedAt(new \DateTime('-365 days'));
        $accessTokenRepository->save($currentUserToken);

        $response = $this->get('/api/user', [
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ]);
        $response->assertStatus(302);
    }
}
