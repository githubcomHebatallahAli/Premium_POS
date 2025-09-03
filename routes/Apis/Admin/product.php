<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProductController;


Route::controller(ProductController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/product','showAll');
   Route::get('/showAll/product/withoutPaginate','showAllProduct');
   Route::get('/showAll/product/LessthanOrEqual5','showProductLessThan5');
   Route::post('/create/product', 'create');
   Route::get('/edit/product/{id}','edit');
   Route::post('/update/product/{id}', 'update');
   Route::delete('/delete/product/{id}', 'destroy');
   Route::get('/showDeleted/product', 'showDeleted');
Route::get('/restore/product/{id}','restore');
Route::delete('/forceDelete/product/{id}','forceDelete');
   });
