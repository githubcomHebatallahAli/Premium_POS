<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProductVariantController;


Route::controller(ProductVariantController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/productVariant','showAll');
   Route::get('/showAll/productVariant/withoutPaginate','showAllproductVariant');
   Route::get('/showAll/productVariant/LessthanOrEqual5','showproductVariantLessThan5');
   Route::post('/create/productVariant', 'create');
   Route::get('/edit/productVariant/{id}','edit');
   Route::post('/update/productVariant/{id}', 'update');
   Route::delete('/delete/productVariant/{id}', 'destroy');
   Route::get('/showDeleted/productVariant', 'showDeleted');
Route::get('/restore/productVariant/{id}','restore');
Route::delete('/forceDelete/productVariant/{id}','forceDelete');
   });