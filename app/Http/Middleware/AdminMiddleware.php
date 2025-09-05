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
        // التحقق من وجود توكن للإدمن
        if (Auth::guard('admin')->check()) {
            return $next($request);
        }
        
        // إذا كان المستخدم موجود في جدول الإدمن ولكن التوكن منتهي
        if ($request->user('admin')) {
            return response()->json([
                'message' => 'Token Expired'
            ], 401);
        }

        // إذا كان المستخدم غير مصرح له بالدخول
        return response()->json([
            'message' => 'Unauthorized User'
        ], 403);
    }
    }

