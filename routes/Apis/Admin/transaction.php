<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TransactionController;


Route::controller(TransactionController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/transaction','showAll');
   Route::get('/showAll/transaction/withoutPaginate','showAlltransaction');
   Route::post('/create/transaction', 'create');
   Route::get('/edit/transaction/{id}','edit');
   Route::post('/update/transaction/{id}', 'update');
   Route::delete('/delete/transaction/{id}', 'destroy');
   Route::get('/showDeleted/transaction', 'showDeleted');
Route::get('/restore/transaction/{id}','restore');
Route::delete('/forceDelete/transaction/{id}','forceDelete');
   });