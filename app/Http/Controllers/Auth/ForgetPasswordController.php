<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Admin;
use App\Http\Controllers\Controller;
use App\Notifications\ResetPasswordNotification;
use App\Http\Requests\Auth\ForgetPasswordRequest;

class ForgetPasswordController extends Controller
{
    public function forgotPassword(ForgetPasswordRequest $request){
        $input = $request->only('email');
        $user = User::where('email',$input)->first();
        $admin = Admin::where('email',$input)->first();

        if (!$user && !$admin) {
            return response()->json([
                'message' => "User or Admin not found."
            ], 404);
        }

        if ($user) {
            $user->notify(new ResetPasswordNotification());
        }


        if ($admin) {
             $admin->notify(new ResetPasswordNotification());
        }

        return response()->json([
            'message' => "Please check your Email."
        ]);

    }
}
