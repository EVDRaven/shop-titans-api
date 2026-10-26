<?php

namespace App\Providers;

use App\Console\DestructiveCommandGuard;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Guarda contra comandos destructivos (migrate:fresh, migrate:refresh, db:wipe)
        // dirigidos a la base de datos de desarrollo protegida.
        Event::listen(CommandStarting::class, DestructiveCommandGuard::class);
    }
}
