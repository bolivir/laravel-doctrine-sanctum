<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bolivir\LaravelDoctrineSanctum;

use Bolivir\LaravelDoctrineSanctum\Commands\DeleteUnusedTokensCommand;
use Bolivir\LaravelDoctrineSanctum\Contracts\IAccessToken;
use Bolivir\LaravelDoctrineSanctum\Contracts\ISanctumUser;
use Bolivir\LaravelDoctrineSanctum\Guard\Guard;
use Bolivir\LaravelDoctrineSanctum\Repository\AccessTokenRepository;
use Bolivir\LaravelDoctrineSanctum\Repository\IAccessTokenRepository;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Illuminate\Auth\RequestGuard;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use LaravelDoctrine\ORM\IlluminateRegistry;
use Ramsey\Uuid\Doctrine\UuidType;

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

        if ($this->app->runningInConsole()) {
            $this->commands([
                DeleteUnusedTokensCommand::class,
            ]);
        }
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

        Type::addType('uuid', \Ramsey\Uuid\Doctrine\UuidType::class);

        config([
            'doctrine.mappings' => [],
            'doctrine.custom_types' => array_merge(
                [
                    UuidType::NAME => UuidType::class,
                ],
                config('doctrine.custom_types', [])
            ),
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
        $this->app->singleton(IAccessTokenRepository::class, fn (Application $app) => $this->createAccessTokenRepository());
        $this->app->alias(IAccessTokenRepository::class, 'sanctum.orm.services.token');
    }

    private function configureGuard()
    {
        Auth::resolved(function ($auth) {
            $auth->extend('sanctum', fn ($app, $name, array $config) => tap($this->createGuard($auth, $config), function ($guard) {
                $this->app->refresh('request', $guard, 'setRequest');
            }));
        });
    }

    private function createAccessTokenRepository(): AccessTokenRepository
    {
        /** @var IlluminateRegistry $registry */
        $registry = $this->app->get('registry');
        $tokenModel = (string) config('sanctum_orm.doctrine.models.token');
        $unusedTokenTTL = (int) config('sanctum_orm.unused_token_ttl', 0);

        $this->validateConfiguration($tokenModel);
        /** @var EntityManagerInterface $em */
        $em = $registry->getManagerForClass($tokenModel);
        $this->ensureValidEntityManager($em, $tokenModel);

        return new AccessTokenRepository($em, $tokenModel, $unusedTokenTTL);
    }

    private function ensureValidEntityManager(?ObjectManager $em, string $tokenModel): void
    {
        if (!$em instanceof \Doctrine\Persistence\ObjectManager) {
            throw new \InvalidArgumentException(sprintf('Can not find valid Entity Manager for "%s" class.', $tokenModel));
        }
    }

    private function validateConfiguration(string $tokenModel): void
    {
        if ('' === $tokenModel || '0' === $tokenModel) {
            throw new \InvalidArgumentException('You have to configure "sanctum.doctrine.token"');
        }

        if (!class_exists($tokenModel)) {
            throw new \InvalidArgumentException(sprintf('Can not use doctrine orm model "%s", class does not exist.', $tokenModel));
        }
    }
}
