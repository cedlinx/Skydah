<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Agency
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
        if (Auth::guard('api')->check() && $request->user()->group->name == 'Agency') {
            return $next($request);
        } else {
            $message = ["message" => "Permission Denied! This operation is reserved for agencies."];
            return response($message, 401);
        }
    }
}
