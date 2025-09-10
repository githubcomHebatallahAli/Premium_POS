<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ImageController;


Route::controller(ImageController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/image','showAll');
   Route::get('/showAll/image/withoutPaginate','showAllImage');
   Route::post('/create/image', 'create');
   Route::get('/edit/image/{id}','edit');
   Route::post('/update/image/{id}', 'update');
   Route::delete('/delete/image/{id}', 'destroy');
   Route::get('/showDeleted/image', 'showDeleted');
Route::get('/restore/image/{id}','restore');
Route::delete('/forceDelete/image/{id}','forceDelete');

   });