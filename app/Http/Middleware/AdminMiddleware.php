<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
      public function handle(Request $request, Closure $next): Response
    {
        try {
            // التحقق من وجود توكن صالح للإدمن
            if (Auth::guard('admin')->check()) {
                return $next($request);
            }
            
            // إذا كان هناك توكن ولكن غير صالح أو منتهي
            if ($request->bearerToken() && !Auth::guard('admin')->check()) {
                return response()->json([
                    'message' => 'Token Expired'
                ], 401);
            }
        } catch (\Exception $e) {
            // في حالة وجود أي خطأ في المصادقة (مثل توكن منتهي)
            if ($request->bearerToken()) {
                return response()->json([
                    'message' => 'Token Expired'
                ], 401);
            }
        }

        // إذا كان المستخدم غير مصرح له بالدخول (لا يوجد توكن أساسًا)
        return response()->json([
            'message' => 'Unauthorized User'
        ], 403);
    }
    }

