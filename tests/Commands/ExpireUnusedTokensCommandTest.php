<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Commands;

use Bolivir\LaravelDoctrineSanctum\Commands\ExpireUnusedTokensCommand;
use Bolivir\LaravelDoctrineSanctum\Repository\AccessTokenRepository;
use Bolivir\LaravelDoctrineSanctum\Repository\IAccessTokenRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Bolivir\LaravelDoctrineSanctum\Fixtures\TestToken;
use Tests\Bolivir\LaravelDoctrineSanctum\TestCase;

class ExpireUnusedTokensCommandTest extends TestCase
{
    /** @var EntityManagerInterface|MockObject */
    private $entityManager;

    /**
     * @var QueryBuilder|MockObject
     */
    private $queryBuilder;

    /**
     * @var MockObject|TestToken
     */
    private $testToken;

    /**
     * @var AbstractQuery|MockObject
     */
    private $query;

    /** @var IAccessTokenRepository */
    private $tokenRepository;

    private ExpireUnusedTokensCommand $expireUnusedTokensCommand;

    /**
     * @var IAccessTokenRepository|mixed|MockObject
     */
    private $tokenRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where', 'orderBy', 'getQuery'])
            ->getMock();
        $this->queryBuilder
            ->method('where')->willReturnSelf();
        $this->queryBuilder
            ->method('orderBy')->willReturnSelf();

        $this->testToken = $this->createMock(TestToken::class);

        $this->query = $this->createMock(AbstractQuery::class);
        $this->tokenRepository = new AccessTokenRepository(
            $this->entityManager,
            TestToken::class,
            1
        );

        $this->expireUnusedTokensCommand = new ExpireUnusedTokensCommand();

        $this->tokenRepositoryMock = $this->createMock(IAccessTokenRepository::class);
    }

    public function testGetUnusedTokens(): void
    {
        $this->query
            ->method('getResult')
            ->willReturn([]);

        $this->queryBuilder
            ->method('getQuery')
            ->willReturn(
                $this->query
            );

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $tokens = $this->tokenRepository->findUnusedTokens();
        $this->assertIsArray($tokens);
    }

    public function testExpireUnusedTokens(): void
    {
        $this->tokenRepositoryMock
            ->method('findUnusedTokens')
            ->willReturn([
                $this->testToken,
                $this->testToken,
                $this->testToken,
            ]);

        $this->tokenRepositoryMock
            ->expects($this->exactly(3))
            ->method('remove')
            ->with($this->testToken);

        $this->expireUnusedTokensCommand->handle($this->tokenRepositoryMock);
    }
}
