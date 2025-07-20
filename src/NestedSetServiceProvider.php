<?php

namespace Indigoram89\NestedSet;

use Illuminate\Support\ServiceProvider;

class NestedSetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/nested-set.php',
            'nested-set'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/nested-set.php' => config_path('nested-set.php'),
            ], 'nested-set-config');

            $this->publishes([
                __DIR__ . '/Migrations/create_nested_sets_table.php' => database_path('migrations/' . date('Y_m_d_His') . '_create_nested_sets_table.php'),
            ], 'nested-set-migrations');

            $this->publishes([
                __DIR__ . '/Views' => resource_path('views/vendor/nested-set'),
            ], 'nested-set-views');

            $this->publishes([
                __DIR__ . '/../resources/js/nested-set-standalone.js' => public_path('vendor/nested-set/js/nested-set-standalone.js'),
            ], 'nested-set-assets');
        }

        $this->loadViewsFrom(__DIR__ . '/Views', 'nested-set');
        
        // Загрузка маршрутов
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}