<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RoleController;


Route::controller(RoleController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/role','showAll');
   Route::post('/create/role', 'create');
   Route::get('/edit/role/{id}','edit');
   Route::post('/update/role/{id}', 'update');
   Route::delete('/delete/role/{id}', 'destroy');
   Route::get('/showDeleted/role', 'showDeleted');
Route::get('/restore/role/{id}','restore');
Route::delete('/forceDelete/role/{id}','forceDelete');
   });
