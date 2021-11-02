<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Bolivir\LaravelDoctrineSanctum;

use Bolivir\LaravelDoctrineSanctum\LaravelDoctrineSanctumProvider;
use Bolivir\LaravelDoctrineSanctum\Repository\IAccessTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Bolivir\LaravelDoctrineSanctum\Fixtures\TestUser;

class LaravelDoctrineSanctumProviderTest extends TestCase
{
    public function testShouldProvideServices()
    {
        $provider = $this->app->getProvider(LaravelDoctrineSanctumProvider::class);
        $this->assertContains(IAccessTokenRepository::class, $provider->provides());
    }

    public function testDoctrineConfiguration()
    {
        $em = app()->get('registry')->getManagerForClass(TestUser::class);
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
    }
}
