<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!session()->has('user_role') || !in_array(session('user_role'), $roles)) {
            return redirect('/')->with('error', 'Unauthorized access. Please login with the correct account.');
        }

        return $next($request);
    }
}