<?php

namespace App\Providers;

use App\Services\Instagram\InstagramPublishClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(InstagramPublishClient::class, fn () => new InstagramPublishClient(
            (string) config('services.instagram.graph_version'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
