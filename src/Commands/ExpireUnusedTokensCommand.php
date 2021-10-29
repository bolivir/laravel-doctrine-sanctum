<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bolivir\LaravelDoctrineSanctum\Commands;

use Bolivir\LaravelDoctrineSanctum\Repository\IAccessTokenRepository;
use Illuminate\Console\Command;

class ExpireUnusedTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sanctum_orm:expire-unused-tokens';

    public function handle(IAccessTokenRepository $repository)
    {
        $tokens = $repository->findUnusedTokens();

        foreach ($tokens as $token) {
            $repository->remove($token);
        }
    }
}
