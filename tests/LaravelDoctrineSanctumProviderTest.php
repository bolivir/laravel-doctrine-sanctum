<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Bolivir\LaravelDoctrineSanctum;

use Bolivir\LaravelDoctrineSanctum\Guard\Guard;
use Bolivir\LaravelDoctrineSanctum\LaravelDoctrineSanctumProvider;
use Bolivir\LaravelDoctrineSanctum\Repository\IAccessTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Auth\RequestGuard;
use Illuminate\Support\Facades\Auth;
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

    public function testSanctumGuardUsesDoctrineGuard()
    {
        $guard = Auth::guard('sanctum');
        $this->assertInstanceOf(RequestGuard::class, $guard);

        $callback = new \ReflectionProperty(RequestGuard::class, 'callback');
        $this->assertInstanceOf(Guard::class, $callback->getValue($guard));
    }
}
