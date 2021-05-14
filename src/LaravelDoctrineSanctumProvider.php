<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bolivir\LaravelDoctrineSanctum;

use Bolivir\LaravelDoctrineSanctum\Contracts\IAccessToken;
use Bolivir\LaravelDoctrineSanctum\Contracts\ISanctumUser;
use Bolivir\LaravelDoctrineSanctum\Guard\Guard;
use Bolivir\LaravelDoctrineSanctum\Repository\AccessTokenRepository;
use Bolivir\LaravelDoctrineSanctum\Repository\IAccessTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Auth\RequestGuard;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class LaravelDoctrineSanctumProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/sanctum_orm.php', 'sanctum_orm'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/sanctum_orm.php' => config_path('sanctum_orm.php'),
        ], 'config');

        $this->configureEntityManager();
        $this->configureGuard();
        $this->configureMiddleware();
        $this->configureTargetEntity();
    }

    public function provides(): array
    {
        return [
            IAccessTokenRepository::class,
        ];
    }

    protected function createGuard($auth, $config): RequestGuard
    {
        return new RequestGuard(
            new Guard(
                $auth,
                $this->app->get(IAccessTokenRepository::class),
                (int) config('sanctum.expiration', 0),
                $config['provider']
            ),
            $this->app['request'],
            $auth->createUserProvider($config['provider'] ?? null)
        );
    }

    protected function configureMiddleware(): void
    {
        $kernel = $this->app->make(Kernel::class);
        $kernel->prependToMiddlewarePriority(EnsureFrontendRequestsAreStateful::class);
    }

    private function configureTargetEntity(): void
    {
        $tokenModel = config('sanctum_orm.doctrine.models.token');
        $userModel = config('sanctum_orm.doctrine.models.user');
        $managerName = config('sanctum_orm.doctrine.manager');

        config([
            'doctrine.mappings' => [],
            'doctrine.resolve_target_entities' => array_merge(
                [
                    IAccessToken::class => $tokenModel,
                    ISanctumUser::class => $userModel,
                ],
                config('doctrine.resolve_target_entities', [])
            ),
        ]);

        $configName = 'doctrine.managers.'.$managerName.'.paths';
        $paths = config($configName, []);
        config([
            $configName => $paths,
        ]);
    }

    private function configureEntityManager(): void
    {
        $this->app->singleton(IAccessTokenRepository::class, function (Application $app) {
            return $this->createAccessTokenRepository();
        });
        $this->app->alias(IAccessTokenRepository::class, 'sanctum.orm.services.token');
    }

    private function configureGuard()
    {
        Auth::resolved(function ($auth) {
            $auth->extend('sanctum', function ($app, $name, array $config) use ($auth) {
                return tap($this->createGuard($auth, $config), function ($guard) {
                    $this->app->refresh('request', $guard, 'setRequest');
                });
            });
        });
    }

    private function createAccessTokenRepository(): AccessTokenRepository
    {
        /** @var EntityManagerInterface $em */
        $em = $this->app->make(EntityManagerInterface::class);
        $tokenModel = (string) config('sanctum_orm.doctrine.models.token');

        $this->validateConfiguration($tokenModel);

        return new AccessTokenRepository(
            $em,
            $tokenModel
        );
    }

    private function validateConfiguration(string $tokenModel): void
    {
        if (empty($tokenModel)) {
            throw new InvalidArgumentException('You have to configure "sanctum.doctrine.token"');
        }

        if (!class_exists($tokenModel)) {
            throw new InvalidArgumentException(sprintf('Can not use doctrine orm model "%s", class does not exist.', $tokenModel));
        }
    }
}
