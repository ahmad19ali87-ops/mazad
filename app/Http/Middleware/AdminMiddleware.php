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
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(403, 'غير مسموح لك بالدخول إلى لوحة إدارة الموقع.');
        }

        return $next($request);
    }
}

