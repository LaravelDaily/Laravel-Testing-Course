<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::redirect('/', 'login');
Route::get('download', [ProductController::class, 'download']);

Route::middleware('auth')->group(function() {
    Route::get('products', [ProductController::class, 'index'])->name('products.index');

    Route::middleware('is_admin')->group(function() {
        Route::get('products/create', [ProductController::class, 'create'])
            ->name('products.create');
        Route::post('products', [ProductController::class, 'store'])
            ->name('products.store');
        Route::get('products/{product}/edit', [ProductController::class, 'edit'])
            ->name('products.edit');
        Route::put('products/{product}', [ProductController::class, 'update'])
            ->name('products.update');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])
            ->name('products.destroy');
    });
});

require __DIR__.'/auth.php';
