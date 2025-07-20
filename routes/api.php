<?php

use Illuminate\Support\Facades\Route;
use Indigoram89\NestedSet\Http\Controllers\NestedSetApiController;

/*
|--------------------------------------------------------------------------
| Nested Set API Routes
|--------------------------------------------------------------------------
|
| API маршруты для управления деревьями через REST API
|
*/

Route::prefix('api/nested-set')->middleware(['api'])->group(function () {
    // Получить список доступных моделей
    Route::get('/models', [NestedSetApiController::class, 'models']);
    
    // Маршруты для работы с конкретной моделью
    Route::prefix('{model}')->group(function () {
        // Получить дерево
        Route::get('/tree', [NestedSetApiController::class, 'tree']);
        
        // CRUD операции с узлами
        Route::post('/nodes', [NestedSetApiController::class, 'store']);
        Route::put('/nodes/{id}', [NestedSetApiController::class, 'update']);
        Route::delete('/nodes/{id}', [NestedSetApiController::class, 'destroy']);
        
        // Переупорядочивание (drag & drop)
        Route::post('/reorder', [NestedSetApiController::class, 'reorder']);
    });
});