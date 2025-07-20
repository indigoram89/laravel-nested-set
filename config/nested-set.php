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

    /*
    |--------------------------------------------------------------------------
    | Available Models
    |--------------------------------------------------------------------------
    |
    | List of models that can be managed through the web interface.
    | Each model should have a unique name and class reference.
    |
    */

    'models' => [
        // Пример конфигурации для модели Category:
        /*
        [
            'name' => 'category',                        // Уникальное имя для URL (латиницей)
            'class' => App\Models\Category::class,       // Класс модели
            'label' => 'Категории',                      // Отображаемое название
            'description' => 'Управление категориями',   // Описание (опционально)
        ],
        */
        
        // Добавьте ваши модели здесь:
    ],
];