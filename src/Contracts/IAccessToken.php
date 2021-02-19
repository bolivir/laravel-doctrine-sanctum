<?php

namespace Bolivir\LaravelDoctrineSanctum\Contracts;

use Laravel\Sanctum\Contracts\HasAbilities;

interface IAccessToken extends HasAbilities
{
    public function id(): int;

    public function changeLastUsedAt(\DateTime $date): void;

    public function lastUsedAt(): ?\DateTime;

    public function changeCreatedAt(\DateTime $date): void;

    public function createdAt(): ?\DateTime;

    public function changeOwner(ISanctumUser $user): void;

    public function owner(): ISanctumUser;

    public function changeName(string $name): void;

    public function name(): string;

    public function token(): string;

    public function changeToken(string $token): void;

    public function changeAbilities(array $abilities = ['*']): void;

    public function abilities(): array;
}
