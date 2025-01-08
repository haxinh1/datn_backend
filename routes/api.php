<?php

use App\Http\Controllers\admin\AttributeController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::resource('attributes', AttributeController::class);
Route::resource('brands', BrandController::class);


// Category api


// Api url = "baseUrl/api/categories"
Route::get('categories', [CategoryController::class, 'index']); // Lấy danh sách danh mục
Route::get('categories/{id}', [CategoryController::class, 'show']); // Lấy thông tin danh mục cụ thể
Route::post('categories/create', [CategoryController::class, 'create']);

