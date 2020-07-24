# Laravel-Socialite-Xero

Xero OAuth2 Provider for Laravel Socialite

[![Packagist](https://img.shields.io/packagist/v/mrstebo/laravel-socialite-xero.svg)](https://packagist.org/packages/mrstebo/laravel-socialite-xero)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![StyleCI](https://styleci.io/repos/281864728/shield)](https://styleci.io/repos/281864728)

This package allows you to use Laravel Socialite using Xero.

## Installation

You can install the package via composer:

```bash
composer require mrstebo/laravel-socialite-xero
```

---

**Note:** if you use Laravel 5.5+ you can skip service provider registration, because it should be auto discovered.

Then you should register service provider in your `config/app.php` file:

```php
'providers' => [
    // Other service providers

    Mrstebo\LaravelSocialiteXero\Provider::class,

]
```

You will also need to add credentials for the OAuth application that you can get using the [Xero Developers Portal](https://developer.xero.com/). They should be placed in your `config/services.php` file. You may copy the example configuration below to get started:

```php
'xero' => [
    'client_id' => env('XERO_CLIENT_ID'),
    'client_secret' => env('XERO_CLIENT_SECRET'),
    'redirect' => env('XERO_REDIRECT'),
],
```

## Basic usage

So now, you are ready to authenticate users! You will need two routes: one for redirecting the user to the OAuth provider, and another for receiving the callback from the provider after authentication. We will access Socialite using the Socialite facade:

```php
<?php

namespace App\Http\Controllers\Auth;

use Socialite;

class AuthController extends Controller
{
    /**
     * Redirect the user to the Xero authentication page.
     *
     * @return Response
     */
    public function redirectToProvider()
    {
        return Socialite::driver('xero')->redirect();
    }

    /**
     * Obtain the user information from Xero.
     *
     * @return Response
     */
    public function handleProviderCallback()
    {
        $user = Socialite::driver('xero')->user();

        // $user->token;
    }
}
```

Of course, you will need to define routes to your controller methods:

```php
Route::get('auth/xero', 'Auth\AuthController@redirectToProvider');
Route::get('auth/xero/callback', 'Auth\AuthController@handleProviderCallback');
```

The redirect method takes care of sending the user to the OAuth provider, while the user method will read the incoming request and retrieve the user's information from the provider.

## Retrieving user details

Once you have a user instance, you can grab a few more details about the user:

```php
$user = Socialite::driver('xero')->user();

// OAuth Two Providers
$token = $user->token;
$refreshToken = $user->refreshToken; // may not always be provided
$expiresIn = $user->expiresIn;
```
