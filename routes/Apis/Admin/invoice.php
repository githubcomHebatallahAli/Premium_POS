<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\InvoiceController;


Route::controller(InvoiceController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/invoice','showAll');
   Route::post('/create/invoice', 'create');
   Route::get('/edit/invoice/{id}','edit');
   Route::post('/update/invoice/{id}', 'update');
   Route::delete('/delete/invoice/{id}', 'destroy');
   Route::get('/showDeleted/invoice', 'showDeleted');
Route::get('/restore/invoice/{id}','restore');
Route::delete('/forceDelete/invoice/{id}','forceDelete');
   });
