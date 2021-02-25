<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bolivir\LaravelDoctrineSanctum\Contracts;

use DateTime;
use Laravel\Sanctum\Contracts\HasAbilities;

interface IAccessToken extends HasAbilities
{
    public function id(): ?int;

    public function changeLastUsedAt(DateTime $date): void;

    public function lastUsedAt(): ?DateTime;

    public function changeCreatedAt(DateTime $date): void;

    public function createdAt(): ?DateTime;

    public function changeOwner(ISanctumUser $user): void;

    public function owner(): ISanctumUser;

    public function changeName(string $name): void;

    public function name(): string;

    public function token(): string;

    public function changeToken(string $token): void;

    /** @param array<string> $abilities */
    public function changeAbilities(array $abilities = ['*']): void;

    /** @return array<string> */
    public function abilities(): array;
}
