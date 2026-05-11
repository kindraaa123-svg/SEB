<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupervisorAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('supervisor')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
