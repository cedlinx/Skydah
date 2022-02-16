<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;    //DELETE IN PRODUCTION

class SuperAdminAuth
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
    ////    $user = User::find(2);
    ////    auth()->login($user);
        //role of -1 is admin; -2 is superadmin; 0 is normal/regular user; >0 represents various external users: agency, enterprise, etc
        //if (Auth::guard('api')->check() && $request->user()->role = -2) { 
        if (Auth::guard('api')->check() && auth()->user()->group->name == 'SuperAdmin') {
            return $next($request);
        } else {
            $message = ["message" => "Permission Denied! You need to be a Super Admin to do this."];
            return response($message, 401);
        }
    
    //    return $next($request);
    }
}
