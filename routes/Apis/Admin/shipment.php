<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ShipmentController;


Route::controller(ShipmentController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/shipment','showAll');
   Route::post('/create/shipment', 'create');
   Route::get('/edit/shipment/{id}','edit');
   Route::post('/update/shipment/{id}', 'update');
   Route::delete('/delete/shipment/{id}', 'destroy');
   Route::get('/showDeleted/shipment', 'showDeleted');
Route::get('/restore/shipment/{id}','restore');
Route::delete('/forceDelete/shipment/{id}','forceDelete');
Route::put('/shipment/{id}/update-paid','updatePaidAmount');
   });
