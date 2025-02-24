<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Bolivir\LaravelDoctrineSanctum\Fixtures;

use Bolivir\LaravelDoctrineSanctum\Contracts\IAccessToken;
use Bolivir\LaravelDoctrineSanctum\Contracts\ISanctumUser;
use Bolivir\LaravelDoctrineSanctum\TAccessToken;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class TestToken implements IAccessToken
{
    use TAccessToken;

    /**
     * @ORM\ManyToOne(targetEntity="Tests\Bolivir\LaravelDoctrineSanctum\Fixtures\TestUser", cascade={"persist"})
     *
     * @var ISanctumUser|null
     */
    protected ISanctumUser $owner;
}
