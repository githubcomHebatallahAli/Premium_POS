<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\VerficationController;

Route::controller(VerficationController::class)->prefix('/otp')->group(
    function () {

Route::post('/send',  'sendOtp');
Route::post('/resend', 'resendOtp');
Route::post('/verify', 'verifyOtp');

    });
