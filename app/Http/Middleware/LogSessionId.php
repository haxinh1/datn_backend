<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogSessionId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Lấy Session ID
        $sessionId = $request->session()->getId();

        // Ghi log Session ID
        Log::info('Session ID: ' . $sessionId);

        return $next($request);
    }
}
