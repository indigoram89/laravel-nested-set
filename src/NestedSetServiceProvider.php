<?php

namespace Indigoram89\NestedSet;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Indigoram89\NestedSet\Http\Livewire\NestedSetManager;

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
        }

        $this->loadViewsFrom(__DIR__ . '/Views', 'nested-set');

        Livewire::component(
            config('nested-set.livewire.component_name', 'nested-set-manager'),
            NestedSetManager::class
        );
    }
}