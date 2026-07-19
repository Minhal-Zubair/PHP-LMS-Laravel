<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NativeSessionBridge
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Start the native PHP session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 2. If native session exists, share it with Laravel
        if (isset($_SESSION['user_id'])) {
            session([
                'user_id' => $_SESSION['user_id'],
                'fullname' => $_SESSION['fullname'] ?? 'User'
            ]);
        }

        return $next($request);
    }
}