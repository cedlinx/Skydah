<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NonDelegate
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
        if ( is_null( $request->user()->company->id ) ) {            
            return $next($request);
        } else {
            $message = ["message" => "Permission Denied to Company Representatives."];
            return response($message, 401);
        }
    }

}
