<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PermissionController;


Route::controller(PermissionController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/permission','showAll');
   Route::post('/create/permission', 'create');
   Route::get('/edit/permission/{id}','edit');
   Route::post('/update/permission/{id}', 'update');
   Route::delete('/delete/permission/{id}', 'destroy');
   Route::get('/showDeleted/permission', 'showDeleted');
Route::get('/restore/permission/{id}','restore');
Route::delete('/forceDelete/permission/{id}','forceDelete');
   });
