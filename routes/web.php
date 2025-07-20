<?php

use Illuminate\Support\Facades\Route;
use Indigoram89\NestedSet\Http\Controllers\NestedSetWebController;

/*
|--------------------------------------------------------------------------
| Nested Set Web Routes
|--------------------------------------------------------------------------
|
| Веб-маршруты для управления деревьями через веб-интерфейс
|
*/

Route::prefix('nested-set')->middleware(['web'])->group(function () {
    Route::get('/', [NestedSetWebController::class, 'index'])->name('nested-set.index');
});