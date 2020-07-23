<?php

namespace Mrstebo\LaravelSocialiteXero;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;

class Provider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $socialite = $this->app->make(Factory::class);

        $config = config()->get('services.xero');

        $provider = $socialite->buildProvider(XeroSocialiteProvider::class, $config);

        $socialite->extend('xero', function () use ($provider) {
            return $provider;
        });
    }
}
