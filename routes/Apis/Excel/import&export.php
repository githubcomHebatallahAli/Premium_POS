<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Excel\ExportController;
use App\Http\Controllers\Excel\ImportController;



Route::group([
    'middleware' => 'admin',
    'prefix' => 'admin'
], function () {
Route::post('/import/products', [ImportController::class, 'importProducts']);
Route::get('/export/products', [ExportController::class, 'exportProducts']);

});
