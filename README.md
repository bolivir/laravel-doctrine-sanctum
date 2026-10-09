# Laravel Doctrine Sanctum

[![CI](https://github.com/bolivir/laravel-doctrine-sanctum/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/bolivir/laravel-doctrine-sanctum/actions/workflows/ci.yml)

[Laravel Sanctum](https://laravel.com/docs/sanctum) API tokens for applications that use
[Laravel Doctrine ORM](https://github.com/laravel-doctrine/orm) instead of Eloquent.

Sanctum stores its personal access tokens as Eloquent models. This package replaces that storage with
Doctrine entities and registers a `sanctum` guard that looks tokens up through Doctrine. Everything else
works the way Sanctum documents it: the `auth:sanctum` middleware, token abilities and token expiration.

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Setup](#setup)
  - [1. Create the access token entity](#1-create-the-access-token-entity)
  - [2. Prepare the user entity](#2-prepare-the-user-entity)
  - [3. Configure the package](#3-configure-the-package)
  - [4. Configure authentication](#4-configure-authentication)
  - [5. Create the database table](#5-create-the-database-table)
- [Usage](#usage)
  - [Issuing tokens](#issuing-tokens)
  - [Protecting routes](#protecting-routes)
  - [Token abilities](#token-abilities)
  - [Revoking tokens](#revoking-tokens)
  - [Token expiration](#token-expiration)
  - [Pruning unused tokens](#pruning-unused-tokens)
- [Version compatibility](#version-compatibility)
- [Upgrading](#upgrading)
- [License](#license)

## Requirements

- PHP 8.2 or higher
- Laravel 11 or 12
- [`laravel/sanctum`](https://github.com/laravel/sanctum) 4.x
- [`laravel-doctrine/orm`](https://github.com/laravel-doctrine/orm) 3.x

## Installation

```bash
composer require bolivir/laravel-doctrine-sanctum
```

The package isn't auto-discovered, so register its service provider in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    Bolivir\LaravelDoctrineSanctum\LaravelDoctrineSanctumProvider::class,
];
```

Then publish the configuration file:

```bash
php artisan vendor:publish --provider="Bolivir\LaravelDoctrineSanctum\LaravelDoctrineSanctumProvider" --tag="config"
```

This creates `config/sanctum_orm.php`.

## Setup

### 1. Create the access token entity

Create an entity that implements `IAccessToken`. The `TAccessToken` trait provides the complete
implementation, including the Doctrine attribute mapping, so all you add is the entity and its table:

```php
<?php

namespace App\Entities;

use Bolivir\LaravelDoctrineSanctum\Contracts\IAccessToken;
use Bolivir\LaravelDoctrineSanctum\TAccessToken;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'personal_access_tokens')]
class AccessToken implements IAccessToken
{
    use TAccessToken;
}
```

The trait maps these fields: `id` (UUID), `name`, `token` (SHA-256 hash, unique), `abilities`,
`lastUsedAt`, `createdAt`, `expiresAt` and the `owner` relation to your user. If you need a different
mapping, implement `IAccessToken` yourself instead of using the trait.

The `uuid` column type comes from `ramsey/uuid-doctrine`. The package registers it for you.

### 2. Prepare the user entity

Your user entity must implement `ISanctumUser`. It extends Laravel's `Authenticatable` contract, so you
don't declare that one separately. The `HasApiTokens` trait implements the token methods:

```php
<?php

namespace App\Entities;

use Bolivir\LaravelDoctrineSanctum\Contracts\IAccessToken;
use Bolivir\LaravelDoctrineSanctum\Contracts\ISanctumUser;
use Bolivir\LaravelDoctrineSanctum\HasApiTokens;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements ISanctumUser
{
    use HasApiTokens;

    /** @var Collection<int, IAccessToken> */
    #[ORM\OneToMany(targetEntity: IAccessToken::class, mappedBy: 'owner', cascade: ['persist'], orphanRemoval: true)]
    protected Collection $accessTokens;

    public function __construct()
    {
        $this->accessTokens = new ArrayCollection();
    }

    // ... your own fields and the Authenticatable methods
}
```

`HasApiTokens` declares the `$accessTokens` collection but doesn't map it. Map it on your entity as
shown above if you use `findToken()`, `revokeToken()` or `revokeAllAccessTokens()` on the user.

Both entities can reference the interfaces (`IAccessToken`, `ISanctumUser`) instead of your concrete
classes. The package configures Doctrine's `resolve_target_entities` to map them to the classes in your
configuration.

### 3. Configure the package

Point `config/sanctum_orm.php` at your entities:

```php
return [
    'doctrine' => [
        'models' => [
            'token' => App\Entities\AccessToken::class,
            'user' => App\Entities\User::class,
        ],
        // The Doctrine entity manager that manages the token entity.
        'manager' => 'default',
    ],

    // Delete tokens that have not been used for this many minutes. 0 keeps them forever.
    'unused_token_ttl' => 0,
];
```

### 4. Configure authentication

Users are loaded through Laravel Doctrine's `doctrine` user provider. In `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'doctrine',
        'model' => App\Entities\User::class,
    ],
],
```

Sanctum registers the `sanctum` guard itself, and this package replaces its driver with a Doctrine-backed
one. You don't need to add the guard to `config/auth.php`. Sanctum's own settings in `config/sanctum.php`
still apply, such as `guard` (for stateful SPA requests) and `expiration`.

### 5. Create the database table

Generate the table from your entity mapping. With
[`laravel-doctrine/migrations`](https://github.com/laravel-doctrine/migrations):

```bash
php artisan doctrine:migrations:diff
php artisan doctrine:migrations:migrate
```

Without migrations, `php artisan doctrine:schema:update` also works.

## Usage

### Issuing tokens

Inject `IAccessTokenRepository` and call `createToken()`. The plain-text token is only available on the
returned `NewAccessToken`. Only its SHA-256 hash is stored, so return it to the client right away.

```php
use Bolivir\LaravelDoctrineSanctum\Repository\IAccessTokenRepository;

class LoginController
{
    public function __construct(private IAccessTokenRepository $tokens)
    {
    }

    public function __invoke(Request $request)
    {
        // ... validate the credentials and load $user

        $newToken = $this->tokens->createToken($user, 'mobile-app', ['orders:read']);

        return ['token' => $newToken->plainTextToken];
    }
}
```

The third argument is the list of abilities. It defaults to `['*']`, which grants every ability.

### Protecting routes

Clients send the token as a bearer token: `Authorization: Bearer <token>`. Protect routes with Sanctum's
middleware:

```php
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
```

### Token abilities

```php
if ($request->user()->tokenCan('orders:read')) {
    // ...
}
```

`$request->user()->currentAccessToken()` returns the `IAccessToken` the request authenticated with.

### Revoking tokens

```php
$user->revokeToken($user->currentAccessToken()); // the current token
$user->revokeAllAccessTokens();                  // every token of this user

$entityManager->flush();
```

These methods remove the tokens from the user's `$accessTokens` collection. They are deleted from the
database on flush when the collection is mapped with `orphanRemoval: true`, as in
[step 2](#2-prepare-the-user-entity).

### Token expiration

A token is rejected when either of these is true:

- it is older than `expiration` in `config/sanctum.php`, in minutes (`null` means it never expires), or
- its own expiry date has passed. Set it with `$token->changeExpiresAt(new \DateTime('+1 week'))`.

### Pruning unused tokens

Set `unused_token_ttl` in `config/sanctum_orm.php`, then run the command, or schedule it:

```bash
php artisan sanctum_orm:delete-unused-tokens
```

```php
// routes/console.php
Schedule::command('sanctum_orm:delete-unused-tokens')->daily();
```

## Version compatibility

| Package | Laravel Sanctum | Laravel Doctrine ORM |
|:--------|:----------------|:---------------------|
| 5.x     | ^4.0            | ^3.0                 |
| 4.x     | ^4.0            | ^2.0                 |
| 3.x     | ^3.0            | ^2.0                 |
| 2.x     | ^2.0            | ^2.0                 |
| 1.x     | ^2.0            | ^1.0                 |

## Upgrading

See [UPGRADE.md](UPGRADE.md). More examples are in the
[wiki](https://github.com/bolivir/laravel-doctrine-sanctum/wiki).

## License

MIT. See [LICENSE](LICENSE).
