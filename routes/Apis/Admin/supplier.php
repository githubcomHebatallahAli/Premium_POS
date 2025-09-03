<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SupplierController;


Route::controller(SupplierController::class)->prefix('/admin')->middleware('admin')->group(
    function () {
   Route::get('/showAll/supplier','showAll');
   Route::post('/create/supplier', 'create');
   Route::get('/edit/supplier/{id}','edit');
   Route::post('/update/supplier/{id}', 'update');
   Route::delete('/delete/supplier/{id}', 'destroy');
   Route::get('/showDeleted/supplier', 'showDeleted');
Route::get('/restore/supplier/{id}','restore');
Route::delete('/forceDelete/supplier/{id}','forceDelete');
Route::patch('notActive/supplier/{id}', 'notActive');
Route::patch('active/supplier/{id}', 'active');
   });
