<?php

namespace App\Providers;

use App\Services\SaleService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SaleService::class);
    }

    public function boot(): void
    {
        //
    }
}