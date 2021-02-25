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
use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait TAccessToken
{
    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int|null
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected string $name;

    /**
     * @ORM\Column(type="string", unique=true)
     *
     * @var string
     */
    protected string $token;

    /**
     * @ORM\Column(type="array", nullable=true)
     *
     * @var array
     */
    protected array $abilities = [];

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTime|null
     */
    protected DateTime $lastUsedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTime|null
     */
    protected DateTime $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="Bolivir\LaravelDoctrineSanctum\Contracts\ISanctumUser", cascade={"persist"})
     *
     * @var ISanctumUser|null
     */
    protected ISanctumUser $owner;

    public function can($ability): bool
    {
        return \in_array('*', $this->abilities, true)
            || \array_key_exists($ability, array_flip($this->abilities));
    }

    public function cant($ability): bool
    {
        return !$this->can($ability);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function changeCreatedAt(DateTime $date): void
    {
        $this->createdAt = $date;
    }

    public function createdAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function changeLastUsedAt(DateTime $date): void
    {
        $this->lastUsedAt = $date;
    }

    public function lastUsedAt(): ?DateTime
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
}
