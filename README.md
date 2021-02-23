# Laravel Doctrine Sanctum
<h3>This package is in development!</h3>

Laravel doctrine integration for the laravel-sanctum package.
This package is an integration package so that the official laravel sanctum package works with doctrine as ORM.

## Versions

Version | Supported Sanctum Version | Supported Laravel-Doctrine ORM Version
:-------|:----------|:----------
~1.0 | ^2.9 | ^1.7


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
This package comes with a trait that defines all needed properties/methods, therefore you can choose to use the `TAccessToken` trait.<br>
```php
class AccessToken implements IAccessToken
{
    use TAccessToken;
}
```
If you want to define the implementation yourself, just implement the interface and dont use the trait.
```php
class AccessToken implements IAccessToken
{
      protected int $id;
    
      protected string $name;
    
      protected string $token;
        
      .......
      .......
}
```
### Updating the UserModel
Your user model should implement the interface `ISanctumUser` ...
TODO

### Creating the database table
Laravel sanctum uses database tables to store the access tokens. There are multiple methods available to generate the database table sql
- If you are using laravel migrations, run `migrations:diff` after the creation of your model and metadata (xml). Then execute the migration with `migrations:migrate`

- Run the plain SQL thats available in this repository. See `Database/create_access_token_table.sql`


### Customization
Todo ...
