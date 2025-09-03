<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CompanyController;


Route::controller(CompanyController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

    Route::get('/showAll/company','showAll');
   Route::post('/create/company', 'create');
   Route::get('/edit/company/{id}','edit');
   Route::post('/update/company/{id}', 'update');
   Route::delete('/delete/company/{id}', 'destroy');
   });