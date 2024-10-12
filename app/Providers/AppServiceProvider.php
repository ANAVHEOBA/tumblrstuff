<?php

namespace App\Providers;

use App\Services\WeiboOAuth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->singleton(WeiboOAuth::class, function ($app) {
            return new WeiboOAuth(
                config('services.weibo.app_key'),
                config('services.weibo.app_secret'),
                config('services.weibo.callback_url')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
