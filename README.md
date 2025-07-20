# Laravel Nested Set

Пакет для управления иерархическими данными в Laravel с использованием паттерна Nested Set. Включает веб-интерфейс на Livewire 3 с поддержкой drag-and-drop.

## Требования

- PHP 8.4+
- Laravel 12.x
- Livewire 3.x

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

### Livewire компонент

Для использования готового интерфейса управления деревом, добавьте компонент на страницу:

```blade
@livewire('nested-set-manager', ['model_class' => App\Models\Category::class])
```

Компонент включает:
- Отображение древовидной структуры
- Drag-and-drop для изменения порядка и вложенности
- CRUD операции
- Поиск по элементам

### Настройка Alpine.js Sort

Для работы drag-and-drop функциональности необходимо подключить Alpine.js с плагином Sort:

```html
<!-- Alpine.js -->
<script defer src="https://unpkg.com/@alpinejs/sort@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<!-- Или через npm -->
<script>
import Alpine from 'alpinejs'
import sort from '@alpinejs/sort'

Alpine.plugin(sort)
Alpine.start()
</script>
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

    // Настройки Livewire компонента
    'livewire' => [
        'component_name' => 'nested-set-manager',
        'enable_drag_drop' => true,
        'enable_lazy_loading' => true,
        'items_per_page' => 50,
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