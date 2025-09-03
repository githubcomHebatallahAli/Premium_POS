<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\BrandController;


Route::controller(BrandController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/brand','showAll');
   Route::get('/showAll/brand/withoutPaginate','showAllBrand');
   Route::post('/create/brand', 'create');
   Route::get('/edit/brand/{id}','edit');
   Route::post('/update/brand/{id}', 'update');
   Route::delete('/delete/brand/{id}', 'destroy');
   Route::get('/showDeleted/brand', 'showDeleted');
Route::get('/restore/brand/{id}','restore');
Route::delete('/forceDelete/brand/{id}','forceDelete');
// Route::patch('/view/brand/{id}','view');
// Route::patch('/notView/brand/{id}','notView');
   });