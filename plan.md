# План разработки Laravel Nested Set пакета

## Изученная информация

### Nested Set в Joomla CMS

#### Основные принципы реализации:
- Использует модифицированную модель Nested Set через класс JTableNested
- Поддержка pre-order tree traversal (обход дерева в прямом порядке)
- Хранение иерархических данных с использованием left/right значений
- Поддержка операций над поддеревьями

#### Структура таблицы БД:
```sql
CREATE TABLE IF NOT EXISTS `nested_sets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `lft` int NOT NULL DEFAULT '0',
  `rgt` int NOT NULL DEFAULT '0',
  `depth` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_left_right` (`lft`,`rgt`),
  KEY `idx_parent_id` (`parent_id`)
);
```

#### Основные методы:
1. **Добавление узлов:**
   - `setLocation($referenceId, $position)` - установка позиции
   - Позиции: 'before', 'after', 'first-child', 'last-child'

2. **Перемещение узлов:**
   - `moveByReference($referenceId, $position, $pk)` - перемещение с потомками

3. **Удаление узлов:**
   - `delete($pk, $children)` - удаление узла (опционально с потомками)

4. **Вспомогательные методы:**
   - `rebuild()` - перестроение дерева
   - `getTree()` - получение поддерева
   - `getPath()` - получение пути к узлу
   - `isLeaf()` - проверка листа
   - `getDescendants()` - получение потомков
   - `getAncestors()` - получение предков

#### Алгоритмы работы с left/right:
- Каждый узел имеет lft и rgt значения
- Все потомки узла имеют lft/rgt между lft и rgt родителя
- rgt всегда больше lft
- Если rgt - lft = 1, узел не имеет потомков

### Livewire 3 и Alpine.js Sort Plugin

#### Для drag-and-drop будем использовать:
- Alpine.js Sort Plugin (https://alpinejs.dev/plugins/sort)
- Встроенная поддержка Alpine.js в Livewire 3
- AJAX обновления без перезагрузки страницы

#### Ключевые моменты интеграции:
1. Alpine.js Sort обрабатывает drag-and-drop на клиенте
2. При изменении порядка вызывается метод Livewire компонента
3. Сервер обновляет Nested Set структуру
4. Livewire обновляет DOM без перезагрузки

## Структура пакета

```
laravel-nested-set/
├── src/
│   ├── Traits/
│   │   └── NestedSetTrait.php
│   ├── Models/
│   │   └── NestedSetModel.php
│   ├── Http/
│   │   └── Livewire/
│   │       └── NestedSetManager.php
│   ├── Views/
│   │   └── livewire/
│   │       └── nested-set-manager.blade.php
│   ├── Migrations/
│   │   └── create_nested_sets_table.php
│   └── NestedSetServiceProvider.php
├── tests/
│   ├── Unit/
│   │   └── NestedSetTraitTest.php
│   ├── Feature/
│   │   └── NestedSetManagerTest.php
│   └── TestCase.php
├── config/
│   └── nested-set.php
├── resources/
│   └── js/
│       └── nested-set.js
├── composer.json
├── phpunit.xml
└── README.md
```

## Функциональные требования

### 1. Trait для моделей (NestedSetTrait):
- Методы для работы с деревом (создание, перемещение, удаление узлов)
- Scopes для выборки узлов
- Автоматическое обновление lft/rgt при операциях
- Поддержка транзакций для целостности данных

### 2. Базовая модель (NestedSetModel):
- Абстрактная модель с подключенным trait
- Настройки по умолчанию
- Валидация операций

### 3. Livewire компонент (NestedSetManager):
- Отображение дерева
- Drag-and-drop через Alpine.js Sort
- CRUD операции без перезагрузки
- Поддержка больших деревьев (lazy loading)

### 4. Миграции:
- Создание таблицы с необходимыми полями
- Индексы для оптимизации

### 5. Тесты:
- Unit тесты для всех методов trait
- Feature тесты для Livewire компонента
- Тесты целостности данных

## Технические детали реализации

### Оптимизации:
1. Использование транзакций для атомарности операций
2. Batch updates для минимизации запросов
3. Индексы на lft/rgt для быстрых выборок
4. Кеширование структуры дерева для read операций

### Безопасность:
1. Валидация всех входных данных
2. Проверка прав доступа перед операциями
3. Защита от race conditions через блокировки

### Совместимость:
- Laravel 12.x
- PHP 8.4+
- Livewire 3.x
- Alpine.js 3.x

## План разработки

1. ✅ Изучить документацию и реализацию Nested Set в Joomla
2. ⏳ Создать структуру Laravel пакета
3. ⏳ Реализовать trait и модель для работы с Nested Set
4. ⏳ Создать миграции для таблиц
5. ⏳ Реализовать Livewire компонент
6. ⏳ Добавить drag-and-drop функциональность с Alpine.js Sort
7. ⏳ Написать автотесты
8. ⏳ Создать README с инструкциями
9. ⏳ Создать GitHub репозиторий и запушить пакет