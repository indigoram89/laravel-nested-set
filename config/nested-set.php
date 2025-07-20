<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nested Set Model Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines the default column names for the nested set
    | implementation. You can override these in your model if needed.
    |
    */

    'columns' => [
        'left' => 'lft',
        'right' => 'rgt',
        'depth' => 'depth',
        'parent_id' => 'parent_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Table Name
    |--------------------------------------------------------------------------
    |
    | The default table name for nested set models. This can be overridden
    | in your model by setting the $table property.
    |
    */

    'table' => 'nested_sets',

    /*
    |--------------------------------------------------------------------------
    | Livewire Component Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the Livewire nested set manager component.
    |
    */

    'livewire' => [
        'component_name' => 'nested-set-manager',
        'enable_drag_drop' => true,
        'enable_lazy_loading' => true,
        'items_per_page' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Options
    |--------------------------------------------------------------------------
    |
    | Options to optimize performance for large trees.
    |
    */

    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'prefix' => 'nested_set_',
    ],
];