<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AgentInvoiceController;


Route::controller(AgentInvoiceController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/agentInvoice','showAll');
   Route::post('/create/agentInvoice', 'create');
   Route::get('/edit/agentInvoice/{id}','edit');
   Route::post('/update/agentInvoice/{id}', 'update');
   Route::delete('/delete/agentInvoice/{id}', 'destroy');
   Route::get('/showDeleted/agentInvoice', 'showDeleted');
Route::get('/restore/agentInvoice/{id}','restore');
Route::delete('/forceDelete/agentInvoice/{id}','forceDelete');
Route::patch('/distribution/agentInvoice/{id}','distribution');
Route::patch('/delivery/agentInvoice/{id}','delivery');
   });
