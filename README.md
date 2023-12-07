# Laravel Doctrine Sanctum

![example branch parameter](https://github.com/bolivir/laravel-doctrine-sanctum/actions/workflows/ci.yml/badge.svg?branch=master)

The original Laravel Sanctum works via eloquent, this package makes it work with laravel-doctrine/orm package (https://github.com/laravel-doctrine/orm)

## Versions
Version | Supported Sanctum Version | Supported Laravel-Doctrine ORM Version
:-------|:----------|:----------
~1.0 | ^2.0 | ^1.0
~2.0 | ^2.0 | ^2.0

# Installation
Start by installing the package with the following command:
```bash
composer require "bolivir/laravel-doctrine-sanctum"
```
To publish the config use:

```bash
php artisan vendor:publish --tag="config" --provider="Bolivir\LaravelDoctrineSanctum\LaravelDoctrineSanctumProvider"
```

# Configuration / Setup
### Creating the Access Token Model
Start by creating your accessTokenModel, and implement the interface 
``IAccessToken``.<br>
```php
class AccessToken implements IAccessToken
{
    use TAccessToken;
}
```
You can use the Trait `TAccessToken` or just implement the interface by your self.
```php
class AccessToken implements IAccessToken
{
      protected string $id;
    
      protected string $name;
    
      protected string $token;
        
      .......
      .......
}
```
### Updating the UserModel
Your user model should implement the interface `ISanctumUser`. 
You dont need to implement the `Authenticable` on your user model directly, it is required inside the `ISanctumUser`
Now you can choose to use the trait `TAccessToken` or implement the interface yourself.

### Creating the database table
Laravel sanctum uses the database to store the access tokens. There are multiple options available to generate the database table sql
- If you are using laravel migrations, run `migrations:diff` after the creation of your model and metadata (xml). Then execute the migration with `migrations:migrate`


Ready to use
---
Implement your login logic and start creating access tokens on succesfull login.

```php
class MyLoginService
{       
      .......
      .......
      public function login() 
      {
        ....
        ....
        $accessToken = $this->tokenRepository->createToken($user, 'tokenName');
      }
}
```

See the WIKI for more detailed steps.
---
https://github.com/bolivir/laravel-doctrine-sanctum/wiki
