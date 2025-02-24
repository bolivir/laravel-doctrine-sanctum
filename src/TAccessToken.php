<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bolivir\LaravelDoctrineSanctum;

use Bolivir\LaravelDoctrineSanctum\Contracts\ISanctumUser;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;

trait TAccessToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?string $id = null;

    #[ORM\Column(type: 'string')]
    protected string $name;

    #[ORM\Column(type: 'string', unique: true)]
    protected string $token;

    #[ORM\Column(type: 'simple_array', nullable: true)]
    protected array $abilities = [];

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected \DateTime $lastUsedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected \DateTime $createdAt;

    #[ORM\ManyToOne(targetEntity: ISanctumUser::class, cascade: ['persist'])]
    protected ISanctumUser $owner;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $expiresAt = null;

    public function can($ability): bool
    {
        return \in_array('*', $this->abilities, true)
            || \array_key_exists($ability, array_flip($this->abilities));
    }

    public function cant($ability): bool
    {
        return !$this->can($ability);
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function changeCreatedAt(\DateTime $date): void
    {
        $this->createdAt = $date;
    }

    public function changeExpiresAt(?\DateTime $date): void
    {
        $this->expiresAt = $date;
    }

    public function createdAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function changeLastUsedAt(\DateTime $date): void
    {
        $this->lastUsedAt = $date;
    }

    public function lastUsedAt(): ?\DateTime
    {
        return $this->lastUsedAt;
    }

    public function owner(): ISanctumUser
    {
        return $this->owner;
    }

    public function changeOwner(ISanctumUser $user): void
    {
        $this->owner = $user;
    }

    public function changeName(string $name): void
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function changeToken(string $token): void
    {
        $this->token = $token;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function changeAbilities(array $abilities = ['*']): void
    {
        $this->abilities = $abilities;
    }

    public function abilities(): array
    {
        return $this->abilities;
    }

    public function expiresAt(): ?\DateTime
    {
        return $this->expiresAt;
    }
}
