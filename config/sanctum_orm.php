<?php

/*
 * This file is part of the Laravel-Doctrine-Sanctum project.
 * (c) Ricardo Mosselman <mosselmanricardo@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

return [
    /*
   |--------------------------------------------------------------------------
   | Doctrine
   |--------------------------------------------------------------------------
   */
    'doctrine' => [
        'models' => [
            'token' => '',
            'user' => '',
        ],
        'manager' => 'default',
    ],

    /*
   |--------------------------------------------------------------------------
   | Delete tokens that haven't been used for a certain time ( in minutes ).
   | Setting it to 0, means it will never be deleted if unused.
   |--------------------------------------------------------------------------
   */
    'unused_token_ttl' => 0,
];
