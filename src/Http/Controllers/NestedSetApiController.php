<?php

namespace Indigoram89\NestedSet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Indigoram89\NestedSet\Models\NestedSetModel;

class NestedSetApiController extends Controller
{
    /**
     * Получить список доступных моделей
     */
    public function models(): JsonResponse
    {
        $models = config('nested-set.models', []);
        
        return response()->json([
            'data' => $models
        ]);
    }
    
    /**
     * Получить дерево для конкретной модели
     */
    public function tree(Request $request, string $model): JsonResponse
    {
        $model_class = $this->resolveModelClass($model);
        
        if (!$model_class) {
            return response()->json([
                'error' => 'Модель не найдена'
            ], 404);
        }
        
        $query = $model_class::query();
        
        // Поиск
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        
        $items = $query->orderBy('lft')->get();
        
        // Построение дерева
        $tree = $this->buildTree($items);
        
        return response()->json([
            'data' => $tree
        ]);
    }
    
    /**
     * Создать новый узел
     */
    public function store(Request $request, string $model): JsonResponse
    {
        $model_class = $this->resolveModelClass($model);
        
        if (!$model_class) {
            return response()->json([
                'error' => 'Модель не найдена'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:' . (new $model_class)->getTable() . ',id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $item = new $model_class([
                'name' => $request->name,
                'slug' => $request->slug ?: \Str::slug($request->name),
            ]);
            
            if ($request->parent_id) {
                $parent = $model_class::find($request->parent_id);
                $item->save();
                $item->makeChildOf($parent);
            } else {
                $item->save();
                $item->makeRoot();
            }
            
            DB::commit();
            
            return response()->json([
                'data' => $item->fresh(),
                'message' => 'Узел успешно создан'
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Ошибка при создании узла: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Обновить узел
     */
    public function update(Request $request, string $model, int $id): JsonResponse
    {
        $model_class = $this->resolveModelClass($model);
        
        if (!$model_class) {
            return response()->json([
                'error' => 'Модель не найдена'
            ], 404);
        }
        
        $item = $model_class::find($id);
        
        if (!$item) {
            return response()->json([
                'error' => 'Узел не найден'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:' . $item->getTable() . ',id|not_in:' . $id,
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $item->update([
                'name' => $request->name,
                'slug' => $request->slug ?: \Str::slug($request->name),
            ]);
            
            // Если изменился родитель
            if ($item->parent_id !== $request->parent_id) {
                if ($request->parent_id) {
                    $parent = $model_class::find($request->parent_id);
                    
                    // Проверка на циклическую ссылку
                    if ($this->isDescendantOf($parent, $item)) {
                        DB::rollBack();
                        return response()->json([
                            'error' => 'Нельзя переместить узел в его же потомка'
                        ], 422);
                    }
                    
                    $item->makeChildOf($parent);
                } else {
                    $item->makeRoot();
                }
            }
            
            DB::commit();
            
            return response()->json([
                'data' => $item->fresh(),
                'message' => 'Узел успешно обновлен'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Ошибка при обновлении узла: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Удалить узел
     */
    public function destroy(string $model, int $id): JsonResponse
    {
        $model_class = $this->resolveModelClass($model);
        
        if (!$model_class) {
            return response()->json([
                'error' => 'Модель не найдена'
            ], 404);
        }
        
        $item = $model_class::find($id);
        
        if (!$item) {
            return response()->json([
                'error' => 'Узел не найден'
            ], 404);
        }
        
        DB::beginTransaction();
        
        try {
            $item->deleteSubtree();
            
            DB::commit();
            
            return response()->json([
                'message' => 'Узел и все его потомки успешно удалены'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Ошибка при удалении узла: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Переупорядочить узлы (для drag & drop)
     */
    public function reorder(Request $request, string $model): JsonResponse
    {
        $model_class = $this->resolveModelClass($model);
        
        if (!$model_class) {
            return response()->json([
                'error' => 'Модель не найдена'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:' . (new $model_class)->getTable() . ',id',
            'items.*.children' => 'nullable|array',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            \Log::info('Reorder request:', ['items' => $request->items]);
            
            $this->processReorderItems($request->items, null, $model_class);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Дерево успешно переупорядочено'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Reorder error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Ошибка при переупорядочивании: ' . $e->getMessage(),
                'details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
    
    /**
     * Рекурсивная обработка переупорядочивания
     */
    protected function processReorderItems(array $items, ?int $parent_id, string $model_class): void
    {
        $previous_sibling = null;
        
        foreach ($items as $item_data) {
            $item = $model_class::find($item_data['id']);
            
            if (!$item) {
                continue;
            }
            
            // Обновляем модель из БД чтобы получить актуальные данные
            $item->refresh();
            
            // Проверяем, изменился ли родитель
            $needs_parent_change = ($item->parent_id !== $parent_id);
            
            if ($needs_parent_change) {
                if ($parent_id) {
                    // Перемещаем к новому родителю
                    $parent = $model_class::find($parent_id);
                    $item->makeChildOf($parent);
                } else {
                    // Делаем корневым
                    $item->makeRoot();
                }
                // Обновляем модель после изменения родителя
                $item->refresh();
            }
            
            // Устанавливаем позицию относительно соседей только если есть предыдущий элемент
            if ($previous_sibling && $previous_sibling->parent_id === $item->parent_id) {
                // Перемещаем после предыдущего элемента на том же уровне
                $item->moveToRightOf($previous_sibling);
            }
            
            // Обрабатываем дочерние элементы
            if (isset($item_data['children']) && is_array($item_data['children'])) {
                $this->processReorderItems($item_data['children'], $item->id, $model_class);
            }
            
            $previous_sibling = $item;
        }
    }
    
    /**
     * Построить дерево из плоского списка
     */
    protected function buildTree($items, $parent_id = null): array
    {
        $tree = [];
        
        foreach ($items as $item) {
            if ($item->parent_id == $parent_id) {
                $node = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'depth' => $item->depth,
                    'parent_id' => $item->parent_id,
                    'lft' => $item->lft,
                    'rgt' => $item->rgt,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'expanded' => true, // По умолчанию раскрываем все узлы
                ];
                
                $children = $this->buildTree($items, $item->id);
                if (!empty($children)) {
                    $node['children'] = $children;
                }
                
                $tree[] = $node;
            }
        }
        
        return $tree;
    }
    
    /**
     * Проверить, является ли узел потомком другого узла
     */
    protected function isDescendantOf($node, $potential_ancestor): bool
    {
        return $node->lft > $potential_ancestor->lft && 
               $node->rgt < $potential_ancestor->rgt;
    }
    
    /**
     * Разрешить класс модели из строки
     */
    protected function resolveModelClass(string $model): ?string
    {
        // Проверяем в конфигурации
        $models = config('nested-set.models', []);
        
        foreach ($models as $model_config) {
            if ($model_config['name'] === $model) {
                return $model_config['class'];
            }
        }
        
        return null;
    }
}