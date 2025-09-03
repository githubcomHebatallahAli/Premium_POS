<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\WithdrawController;


Route::controller(WithdrawController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/withdraw','showAll');
   Route::post('/create/withdraw', 'create');
   Route::get('/edit/withdraw/{id}','edit');
   Route::post('/update/withdraw/{id}', 'update');
   Route::delete('/delete/withdraw/{id}', 'destroy');
   Route::get('/showDeleted/withdraw', 'showDeleted');
Route::get('/restore/withdraw/{id}','restore');
Route::delete('/forceDelete/withdraw/{id}','forceDelete');
   });
