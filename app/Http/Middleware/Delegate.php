<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;




class Delegate
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
        //if ( is_null( $request->user()->company->id ) || ( auth()->user()->group_id < 6 ) ) {            
        if ( auth()->user()->role_id != 1 && auth()->user()->group_id < 6 ) {
            $message = ["message" => "Permission Denied! This operation is reserved for Company Representatives & Skydah Admins."];
            return response($message, 401);
        } else {
            return $next($request);
        }
    }

}
