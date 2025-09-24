<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DamageProductController;


Route::controller(DamageProductController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/damage','showAll');
   Route::get('/showAll/damage/withoutPaginate','showAllDamageProduct');
   Route::post('/create/damage', 'create');
   Route::get('/edit/damage/{id}','edit');
   Route::post('/update/damage/{id}', 'update');
   Route::delete('/delete/damage/{id}', 'destroy');
   Route::get('/showDeleted/damage', 'showDeleted');
Route::get('/restore/damage/{id}','restore');
Route::delete('/forceDelete/damage/{id}','forceDelete');

Route::put('/repaired/damage/{id}','repaired');
Route::put('/return/damage/{id}','return');

   });