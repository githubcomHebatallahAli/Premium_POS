<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PurposeController;


Route::controller(PurposeController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/purpose','showAll');
   Route::get('/showAll/purpose/withoutPaginate','showAllpurpose');
   Route::post('/create/purpose', 'create');
   Route::get('/edit/purpose/{id}','edit');
   Route::post('/update/purpose/{id}', 'update');
   Route::delete('/delete/purpose/{id}', 'destroy');
   Route::get('/showDeleted/purpose', 'showDeleted');
Route::get('/restore/purpose/{id}','restore');
Route::delete('/forceDelete/purpose/{id}','forceDelete');
   });