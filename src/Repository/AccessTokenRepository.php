<?php

namespace Bolivir\LaravelDoctrineSanctum\Repository;

use Bolivir\LaravelDoctrineSanctum\Contracts\IAccessToken;
use Bolivir\LaravelDoctrineSanctum\Contracts\ISanctumUser;
use Bolivir\LaravelDoctrineSanctum\NewAccessToken;
use Doctrine\Persistence\ObjectManager;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\TransientToken;

class AccessTokenRepository implements IAccessTokenRepository
{
    protected ObjectManager $em;

    protected string $tokenModel;

    public function __construct(ObjectManager $em, string $tokenModel)
    {
        $this->em = $em;
        $this->tokenModel = $tokenModel;
    }

    public function createToken(ISanctumUser $user, string $name, array $abilities = ['*']): NewAccessToken
    {
        /** @var IAccessToken $token */
        $plainTextToken = Str::random(80);
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
