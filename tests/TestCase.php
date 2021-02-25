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
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\SanctumServiceProvider;
use LaravelDoctrine\ORM\DoctrineServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Tests\Bolivir\LaravelDoctrineSanctum\Fixtures\TestToken;
use Tests\Bolivir\LaravelDoctrineSanctum\Fixtures\TestUser;

class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            DoctrineServiceProvider::class,
            SanctumServiceProvider::class,
            LaravelDoctrineSanctumProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        /** @var \Illuminate\Config\Repository $config */
        $config = $app['config'];

        $config->set('doctrine.managers.default.paths', [
            __DIR__.'/Fixtures/',
        ]);

        $config->set('auth.providers.users.driver', 'doctrine');
        $config->set('sanctum.doctrine.models.token', TestToken::class);
        $config->set('sanctum.doctrine.models.user', TestUser::class);
        $config->set('sanctum.doctrine.manager', 'default');
        $config->set('sanctum.expiration', 3600);
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $email
     *
     * @return object|TestUser|null
     */
    protected function createUser($username = 'test', $password = 'test', $email = 'test@example.com')
    {
        $user = $this->getRepository(TestUser::class)
            ->findOneBy(['username' => $username]);
        if (null === $user) {
            $manager = $this->getEntityManager(TestUser::class);
            $user = new TestUser();
            $user->setUsername($username)
                ->setEmail($email)
                ->setPassword(Hash::make($password));

            $manager->persist($user);
            $manager->flush();
        }

        return $user;
    }

    protected function getRepository(string $className): ObjectRepository
    {
        $manager = $this->getEntityManager($className);

        return $manager->getRepository($className);
    }

    protected function getEntityManager($className): ObjectManager
    {
        return app()->get('registry')->getManagerForClass($className);
    }

    private function createDatabase()
    {
        $registry = app()->get('registry');

        foreach ($registry->getManagers() as $manager) {
            $meta = $manager->getMetadataFactory()->getAllMetadata();
            $tool = new SchemaTool($manager);
            try {
                $tool->dropSchema($meta);
                $tool->createSchema($meta);
            } catch (ToolsException $e) {
                throw new \InvalidArgumentException("Database schema is not buildable: {$e->getMessage()}", $e->getCode(), $e);
            }
        }
    }
}
