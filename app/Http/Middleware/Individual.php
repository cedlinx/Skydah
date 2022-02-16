<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

class Individual
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
        if (Auth::guard('api')->check() && $request->user()->group->name == 'Individual') {
            return $next($request);
        } else {
            $message = ["message" => "Access Denied"];
            return response($message, 401);
        }
    }
}
