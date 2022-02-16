<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyAdmin
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
        if (Auth::guard('api')->check() && ( auth()->user()->group->name == 'Agency' || auth()->user()->group->name == 'Enterprise'
                                        || auth()->user()->group->name == 'Admin' || auth()->user()->group->name == 'SuperAdmin' 
                                        || auth()->user()->role->id == 1) 
        ) {
            return $next($request);
        } else {
            return response()->json([
                'success' => false,
                'message' => "Permission Denied for non company admin!"
            ], 401);
        }
    }
}
