<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Admin;
use Ichtrojan\Otp\Otp;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\ResetPasswordRequest;

class ResetPasswordController extends Controller
{
    private $otp;

    public function __construct(){
        $this->otp = new Otp;
    }

    public function resetPassword(ResetPasswordRequest $request){

        $otp2 = $this->otp->validate($request->email, $request->otp);
        if (!$otp2->status) {
            return response()->json(['error' => $otp2], 401);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->update(['password' => Hash::make($request->password)]);
        } else {

            $admin = Admin::where('email', $request->email)->first();
            if ($admin) {
                $admin->update(['password' => Hash::make($request->password)]);
            } else {
                return response()->json(['error' => 'User or Admin not found.'], 404);
            }
        }

        return response()->json([
            'message' => "The password has been reset successfully."
        ]);
    }

}
