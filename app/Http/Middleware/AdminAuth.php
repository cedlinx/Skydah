<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;    //DELETE IN PRODUCTION

class AdminAuth
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
        //role of -1 is admin; -2 is superadmin; 0 is normal/regular user; >0 represents various external users: agency, enterprise, etc
        // < 0 implies -ve role which would be -1 (admin) or -2 (super admin who also has admin rights)
        ////$user = User::find(1);  //DELETE
        ////auth()->login($user);   //DELETE
        //if (Auth::guard('api')->check() && $request->user()->role < 0) {
        if (Auth::guard('api')->check() && ( $request->user()->group->name == 'Admin' || $request->user()->group->name == 'SuperAdmin') ) {
            return $next($request);
        } else {
            $message = ["message" => "Permission Denied for non-Admin!"];
            return response($message, 401);
        }

    //    return $next($request);
    }
}
