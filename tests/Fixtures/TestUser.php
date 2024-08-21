<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Bolivir\LaravelDoctrineSanctum\Fixtures;

use Bolivir\LaravelDoctrineSanctum\Contracts\ISanctumUser;
use Bolivir\LaravelDoctrineSanctum\HasApiTokens;
use Doctrine\ORM\Mapping as ORM;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Ramsey\Uuid\Doctrine\UuidGenerator;

/**
 * @ORM\Entity
 */
class TestUser implements ISanctumUser, Jsonable, Arrayable
{
    use HasApiTokens;

    /**
     * @ORM\Id
     *
     * @ORM\Column(type="uuid", unique=true)
     *
     * @ORM\GeneratedValue(strategy="CUSTOM")
     *
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     *
     * @var string
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true)
     *
     * @var string
     */
    protected $username;

    /**
     * @ORM\Column(type="string", unique=true)
     *
     * @var string
     */
    protected $email;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $password;

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function toArray()
    {
        $accessToken = $this->accessToken;

        return [
            'id' => $this->getId(),
            'username' => $this->getUsername(),
            'email' => $this->getEmail(),
            'accessToken' => null !== $accessToken && ($accessToken instanceof Arrayable) ? $this->accessToken->toArray() : [],
        ];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return TestUser
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return TestUser
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return TestUser
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getAuthIdentifierName()
    {
        // TODO: Implement getAuthIdentifierName() method.
    }

    public function getAuthIdentifier()
    {
        // TODO: Implement getAuthIdentifier() method.
    }

    public function getAuthPassword()
    {
        // TODO: Implement getAuthPassword() method.
    }

    public function getRememberToken()
    {
        // TODO: Implement getRememberToken() method.
    }

    public function setRememberToken($value)
    {
        // TODO: Implement setRememberToken() method.
    }

    public function getRememberTokenName()
    {
        // TODO: Implement getRememberTokenName() method.
    }

    public function getAuthPasswordName()
    {
        // TODO: Implement getAuthPasswordName() method.
    }
}
