<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DeptController;


Route::controller(DeptController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/dept','showAll');
   Route::post('/create/dept', 'create');
   Route::get('/edit/dept/{id}','edit');
   Route::put('/dept/{id}/update-paid','updatePaidAmount');
   Route::post('/update/dept/{id}', 'update');
   Route::delete('/delete/dept/{id}', 'destroy');
   Route::get('/showDeleted/dept', 'showDeleted');
Route::get('/restore/dept/{id}','restore');
Route::delete('/forceDelete/dept/{id}','forceDelete');
   });
