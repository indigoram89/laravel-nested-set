# Laravel Nested Set

[![Latest Version on Packagist](https://img.shields.io/packagist/v/indigoram89/laravel-nested-set.svg?style=flat-square)](https://packagist.org/packages/indigoram89/laravel-nested-set)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/indigoram89/laravel-nested-set/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/indigoram89/laravel-nested-set/actions?query=workflow%3Atests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/indigoram89/laravel-nested-set.svg?style=flat-square)](https://packagist.org/packages/indigoram89/laravel-nested-set)

Пакет для управления иерархическими данными в Laravel с использованием паттерна Nested Set. Включает современный веб-интерфейс на Vue.js 3 с поддержкой drag-and-drop и REST API.

## Требования

- PHP 8.4+
- Laravel 12.x

## Установка

### Через Composer из GitHub

```bash
composer require indigoram89/laravel-nested-set
```

### Публикация конфигурации

```bash
php artisan vendor:publish --tag=nested-set-config
```

### Публикация миграций

```bash
php artisan vendor:publish --tag=nested-set-migrations
php artisan migrate
```

### Публикация views (опционально)

```bash
php artisan vendor:publish --tag=nested-set-views
```

### Публикация assets для веб-интерфейса

```bash
php artisan vendor:publish --tag=nested-set-assets
```

## Использование

### Создание модели

Создайте модель, наследующую `NestedSetModel`:

```php
<?php

namespace App\Models;

use Indigoram89\NestedSet\Models\NestedSetModel;

class Category extends NestedSetModel
{
    protected $table = 'categories';
    
    protected $fillable = ['name', 'slug'];
}
```

Или используйте trait в существующей модели:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Indigoram89\NestedSet\Traits\NestedSetTrait;

class Category extends Model
{
    use NestedSetTrait;
    
    protected $fillable = ['name', 'slug'];
}
```

### Миграция

Создайте миграцию для вашей таблицы:

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('parent_id')->nullable()->index();
    $table->integer('lft')->default(0)->index();
    $table->integer('rgt')->default(0)->index();
    $table->unsignedInteger('depth')->default(0);
    $table->string('name');
    $table->string('slug')->nullable()->index();
    $table->timestamps();
    
    $table->index(['lft', 'rgt']);
    $table->index(['parent_id', 'lft']);
});
```

### Основные операции

#### Создание корневого узла

```php
$root = Category::create(['name' => 'Root Category']);
$root->makeRoot();
```

#### Создание дочернего узла

```php
$child = Category::create(['name' => 'Child Category']);
$child->makeChildOf($parent);
```

#### Перемещение узлов

```php
// Переместить слева от узла
$node->moveToLeftOf($target);

// Переместить справа от узла
$node->moveToRightOf($target);

// Сделать дочерним узлом
$node->makeChildOf($parent);
```

#### Получение данных

```php
// Получить всех потомков
$descendants = $node->getDescendants();

// Получить всех предков
$ancestors = $node->getAncestors();

// Получить прямых потомков
$children = $node->getChildren();

// Получить родителя
$parent = $node->getParent();

// Получить соседние узлы
$siblings = $node->getSiblings();

// Получить путь до узла
$path = $node->getPath();

// Получить все дерево
$tree = Category::query()->getTree();
```

#### Проверки

```php
// Является ли узел корневым
$node->isRoot();

// Является ли узел листом (без потомков)
$node->isLeaf();

// Проверка целостности дерева
$node->isValidNestedSet();
```

#### Удаление

```php
// Удалить узел с потомками
$node->deleteSubtree();
```

### Scopes

```php
// Получить корневые узлы
Category::query()->roots()->get();

// Получить листья
Category::query()->leaves()->get();

// Получить узлы определенной глубины
Category::query()->withDepth(2)->get();
```

### Веб-интерфейс на Vue.js

Пакет включает современный веб-интерфейс для управления деревьями с использованием Vue.js 3, Tailwind CSS и REST API.

#### Возможности

- 🎯 Выбор модели для управления
- 🌳 Визуализация дерева с анимациями
- 🔍 Поиск по дереву
- ➕ Создание новых узлов
- ✏️ Редактирование существующих узлов
- 🗑️ Удаление узлов с подтверждением
- 🔄 Drag & Drop для перемещения узлов
- 📱 Адаптивный дизайн

#### Установка веб-интерфейса

1. Опубликуйте конфигурацию и assets:

```bash
# Опубликовать всё
php artisan vendor:publish --provider="Indigoram89\NestedSet\NestedSetServiceProvider"

# Или по отдельности:
php artisan vendor:publish --tag=nested-set-config
php artisan vendor:publish --tag=nested-set-assets
```

2. Настройте модели в файле `config/nested-set.php`:

```php
'models' => [
    [
        'name' => 'category',
        'class' => App\Models\Category::class,
        'label' => 'Категории',
        'description' => 'Управление категориями товаров',
    ],
    [
        'name' => 'menu',
        'class' => App\Models\MenuItem::class,
        'label' => 'Меню',
        'description' => 'Управление пунктами меню',
    ],
],
```

3. Откройте в браузере:

```
http://your-app.test/nested-set
```

#### API Endpoints

Интерфейс использует следующие API endpoints:

- `GET /api/nested-set/models` - получить список моделей
- `GET /api/nested-set/{model}/tree` - получить дерево
- `POST /api/nested-set/{model}/nodes` - создать узел
- `PUT /api/nested-set/{model}/nodes/{id}` - обновить узел
- `DELETE /api/nested-set/{model}/nodes/{id}` - удалить узел
- `POST /api/nested-set/{model}/reorder` - переупорядочить узлы

#### Безопасность

- Все запросы защищены CSRF токеном
- Валидация на стороне сервера
- Защита от циклических ссылок при перемещении узлов

#### Добавление аутентификации

Для защиты интерфейса добавьте middleware в маршруты:

```php
Route::prefix('nested-set')
    ->middleware(['web', 'auth', 'can:manage-trees'])
    ->group(function () {
        Route::get('/', [NestedSetWebController::class, 'index']);
    });
```

## Конфигурация

Файл конфигурации `config/nested-set.php`:

```php
return [
    // Названия колонок
    'columns' => [
        'left' => 'lft',
        'right' => 'rgt',
        'depth' => 'depth',
        'parent_id' => 'parent_id',
    ],

    // Имя таблицы по умолчанию
    'table' => 'nested_sets',

    // Модели для веб-интерфейса
    'models' => [
        // Добавьте ваши модели здесь
    ],

    // Настройки производительности
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'prefix' => 'nested_set_',
    ],
];
```

## Примеры использования

### Построение меню навигации

```php
$categories = Category::query()->roots()->get();

foreach ($categories as $root) {
    echo $root->name;
    
    foreach ($root->getChildren() as $child) {
        echo '-- ' . $child->name;
        
        foreach ($child->getChildren() as $grandchild) {
            echo '---- ' . $grandchild->name;
        }
    }
}
```

### Хлебные крошки

```php
$category = Category::find($id);
$breadcrumbs = $category->getPath();

foreach ($breadcrumbs as $crumb) {
    echo $crumb->name . ' > ';
}
```

### Перестроение дерева

Если структура дерева была повреждена:

```php
$model = new Category();
$model->rebuild();
```

## Тестирование

Запустите тесты:

```bash
vendor/bin/phpunit
```

## Лицензия

MIT License