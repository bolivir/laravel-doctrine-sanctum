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
use Doctrine\Persistence\ObjectManager;
use Illuminate\Auth\RequestGuard;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use LaravelDoctrine\ORM\IlluminateRegistry;

class LaravelDoctrineSanctumProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/Config/sanctum.php' => config_path('sanctum.php'),
        ]);

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
        $tokenModel = config('sanctum.doctrine.models.token');
        $userModel = config('sanctum.doctrine.models.user');
        $managerName = config('sanctum.doctrine.manager');

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
        /** @var IlluminateRegistry $registry */
        $registry = $this->app->get('registry');
        $tokenModel = (string) config('sanctum.doctrine.models.token');

        $this->validateConfiguration($tokenModel);
        $em = $registry->getManagerForClass($tokenModel);
        $this->ensureValidEntityManager($em, $tokenModel);

        return new AccessTokenRepository($em, $tokenModel);
    }

    private function ensureValidEntityManager(?ObjectManager $em, string $tokenModel): void
    {
        if (null === $em) {
            throw new InvalidArgumentException(sprintf('Can not find valid Entity Manager for "%s" class.', $tokenModel));
        }
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
