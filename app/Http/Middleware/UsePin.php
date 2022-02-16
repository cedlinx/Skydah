<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Asset;

class UsePin
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
        //if ( (Auth::guard('api')->check() && $request->pin == $request->user()->pinSOS) || ( Auth::guard('api')->check() && $request->pin == $request->user()->pin) ) {
        if ( (Auth::guard('api')->check() && $request->pin == auth()->user()->pinSOS) || ( Auth::guard('api')->check() && $request->pin == auth()->user()->pin) ) {            
            //if ($request->pin == $request->user()->pinSOS) {//User is probably under duress
            if ($request->pin == auth()->user()->pinSOS) {//User is probably under duress
                //mark asset as SOS/suspect discretely
                if ($asset = Asset::find($request->id))
                {
                    $asset->sos = 1;
                    $asset->save();
                }
                //get GPS location & notify the authorities //add sosLocation, sosLat, sosLng to assets???
                //notify a distant friend???
            }
            return $next($request);
        } else {
            $message = ["message" => "Permission Denied! Please, enter your PIN and try again."];
            return response($message, 401);
        }
    }
}
