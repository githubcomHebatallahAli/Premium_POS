<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\ForgetPasswordController;

Route::group([
    'middleware' => 'api',
    'prefix' => 'password'
], function () {
    Route::post('/forgot', [ForgetPasswordController::class, 'forgotPassword']) ;
    Route::post('/reset', [ResetPasswordController::class, 'resetPassword']);
});
