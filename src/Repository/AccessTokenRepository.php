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
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\TransientToken;

class AccessTokenRepository implements IAccessTokenRepository
{
    protected EntityManagerInterface $em;

    protected string $tokenModel;

    protected int $unusedTokenExpiration;

    public function __construct(EntityManagerInterface $em, string $tokenModel, int $unusedTokenExpiration = 0)
    {
        $this->em = $em;
        $this->tokenModel = $tokenModel;
        $this->unusedTokenExpiration = $unusedTokenExpiration;
    }

    public function createToken(ISanctumUser $user, string $name, array $abilities = ['*']): NewAccessToken
    {
        $plainTextToken = Str::random(80);
        /** @var IAccessToken $token */
        $token = new $this->tokenModel();
        $token->changeCreatedAt(now());
        $token->changeName($name);
        $token->changeOwner($user);
        $token->changeToken(hash('sha256', $plainTextToken));
        $token->changeAbilities($abilities);
        $token->changeLastUsedAt(now());
        $this->save($token);

        return new NewAccessToken($token, $token->id().'|'.$plainTextToken);
    }

    public function findToken(string $token): ?IAccessToken
    {
        $repository = $this->em->getRepository($this->tokenModel);

        if (false === strpos($token, '|')) {
            return $repository->findOneBy(['token' => hash('sha256', $token)]);
        }

        [$id, $token] = explode('|', $token, 2);

        if ($accessToken = $repository->find($id)) {
            return hash_equals($accessToken->token(), hash('sha256', $token)) ? $accessToken : null;
        }

        return null;
    }

    public function deleteUnusedTokens(): ?int
    {
        if ($this->unusedTokenExpiration > 0) {
            $result = $this->em
                ->createQueryBuilder()
                ->delete()
                ->where("last_used_at < DATESUB(CURRENT_DATE(), {$this->unusedTokenExpiration}, 'MINUTE')")
                ->getQuery()
                ->execute();

            if (is_numeric($result)) {
                return (int) $result;
            }
        }

        return null;
    }

    /**
     * @param Authenticatable|ISanctumUser $user
     */
    public function createTransientToken($user): ?ISanctumUser
    {
        $user->withAccessToken(new TransientToken());

        return $user;
    }

    public function updateAccessToken(IAccessToken $token)
    {
        $token->changeLastUsedAt(now());
        $token->owner()->withAccessToken($token);
        $this->save($token);

        return $token->owner();
    }

    public function save(IAccessToken $token): void
    {
        $this->em->persist($token);
        $this->em->flush();
    }
}
