<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Commands;

use Bolivir\LaravelDoctrineSanctum\Commands\DeleteUnusedTokensCommand;
use Bolivir\LaravelDoctrineSanctum\Repository\AccessTokenRepository;
use Bolivir\LaravelDoctrineSanctum\Repository\IAccessTokenRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Console\OutputStyle;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
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
     * @var AbstractQuery|MockObject
     */
    private $query;

    /** @var IAccessTokenRepository */
    private $tokenRepository;

    private DeleteUnusedTokensCommand $deleteUnusedTokensCommand;

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
            ->onlyMethods(['delete', 'where', 'getQuery'])
            ->getMock();

        $this->queryBuilder
            ->method('delete')->willReturnSelf();
        $this->queryBuilder
            ->method('where')->willReturnSelf();

        $this->query = $this->createMock(AbstractQuery::class);
        $this->tokenRepository = new AccessTokenRepository(
            $this->entityManager,
            TestToken::class,
            1
        );

        $this->deleteUnusedTokensCommand = new DeleteUnusedTokensCommand();

        $this->tokenRepositoryMock = $this->createMock(IAccessTokenRepository::class);
    }

    public function testGetUnusedTokens(): void
    {
        $this->query
            ->method('execute')
            ->willReturn(3);

        $this->queryBuilder
            ->method('getQuery')
            ->willReturn(
                $this->query
            );

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $result = $this->tokenRepository->deleteUnusedTokens();
        $this->assertIsInt($result);
    }

    public function testExpireUnusedTokens(): void
    {
        $this->tokenRepositoryMock
            ->method('deleteUnusedTokens')
            ->willReturn(3);

        $this->tokenRepositoryMock
            ->expects($this->once())
            ->method('deleteUnusedTokens');

        $inputInterface = $this->getMockBuilder(InputInterface::class)
            ->onlyMethods([])
            ->getMock();

        $outputStyle = $this->getMockBuilder(OutputStyle::class)
            ->setConstructorArgs([
                $inputInterface,
                new NullOutput(),
            ])
            ->onlyMethods(['writeLn'])
            ->getMock();

        $outputStyle
            ->expects($this->once())
            ->method('writeLn')
            ->with('<info>3 unused tokens found and deleted.</info>');

        $this->setProtectedProperty(
            $this->deleteUnusedTokensCommand,
            'output',
            $outputStyle
        );

        $this->deleteUnusedTokensCommand->handle($this->tokenRepositoryMock);
    }
}
