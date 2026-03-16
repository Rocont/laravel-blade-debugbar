<?php

namespace Rocont\BladeDebugbar;

use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class BladeDebugbarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/blade-debugbar.php', 'blade-debugbar');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/blade-debugbar.php' => config_path('blade-debugbar.php'),
        ], 'blade-debugbar-config');

        if (! $this->app->bound('debugbar')) {
            return;
        }

        $collector = new BladeVariablesCollector(
            config('blade-debugbar.group_by_view', true),
            config('blade-debugbar.excluded_variables', []),
            config('blade-debugbar.shared_mode', 'mark'),
        );

        Debugbar::addCollector($collector);

        View::composer('*', function ($view) use ($collector) {
            $collector->addView($view);
        });
    }
}