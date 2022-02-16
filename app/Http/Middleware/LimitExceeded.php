<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\GetSettings;

class LimitExceeded
{
    use GetSettings;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ( (Auth::guard('api')->check() && auth()->user()->assets->count() == 0 && auth()->user()->id <= $this->setting('no_of_lucky_first_subscribers')) //Qualifies for lucky first few
                || (Auth::guard('api')->check() && (auth()->user()->assets->count() < auth()->user()->asset_limit) )
            )
        {
            return $next($request);
        } else {
            if ((auth()->user()->assets->count() >= auth()->user()->asset_limit))   //should NEVER be greater than
            return response()->json([
                'success' => false,
                'message' => 'You have exhausted your quota. Please, upgrade to a higher plan to continue.',
            ], 422);

            if (auth()->user()->id > $this->setting('no_of_lucky_first_subscribers')) //ASSUMES that the users table would be cleared and the the ID reset to 0, so that the first subscribers will correspond setting(no_of_lucky_first_subscribers)
                return response()->json([
                    'success' => false,
                    'message' => 'Please, upgrade to a paid plan to continue.',
                ], 422);

            return response()->json([
                'success' => false,
                'message' => 'You can only add 1 asset as a free user. Please, upgrade to a paid plan to continue.',
            ], 422);
        }
    }
}
