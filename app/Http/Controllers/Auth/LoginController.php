<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

//social login
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;     //NOT required in production

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    //from here down handles social login... above is the default
    protected $providers = [
        'github','facebook','google','twitter', 'apple' //these are the drivers
    ];


    
    //not required for API
    public function show()
    {
        //return view('auth.login');
        return response()->json('Select a Social Account to proceed', 200);
    }
    
    private function isProviderAllowed($driver)
    {
        return in_array($driver, $this->providers) && config()->has("services.{$driver}");
    }
    
    //This function is required ONLY if we are building a frontend here...
    public function redirectToProvider($driver)
    {
        if( ! $this->isProviderAllowed($driver) ) {
            //return $this->sendFailedResponse("{$driver} is not currently supported");
            return response()->json($this->sendFailedResponse("{$driver} is not currently supported"), 401);
        }

        try {
            //return Socialite::driver($driver)->redirect();
            $response = Socialite::driver($driver)->redirect();
            return response()->json($response, 200);
        } catch (Exception $e) {
            // Display some simple failure message
            //return $this->sendFailedResponse($e->getMessage());
            return response()->json($this->sendFailedResponse("Oops! Something went wrong and {$driver} could not login"), 400);
        }
    }

    protected function loginOrCreateAccount($user, $Provider)   //($providerUser, $driver)
    {
        // check whether use already has an account
    //    $user = User::where('email', $providerUser->getEmail())->first();
        $socialUser = User::where('email', $user['email'])->first();

        // if user already exists
        if( $socialUser ) {
            // update the avatar and provider that may have changed
            $socialUser->update([
                'avatar' => $user['avatar'],  //$providerUser->avatar,
                'provider' => $user['provider'],    //$driver,
                'provider_id' => $user['provider_id'],
                'access_token' =>$user['access_token']
            ]);
        } else {
            // create a new user
            if( !empty( $user['email'] )) {  //($providerUser->getEmail()){ //Check whether email exists or not. If it exists create a new user: REQUIRED for social accounts like Facebook that were opened WITHOUT an email (but phone number)
                $socialUser = User::create([
                    'name' => $user['name'],    //$providerUser->getName(),
                    'email' => $user['email'],  //$providerUser->getEmail(),
                    'avatar' => $user['avatar'],
                    'provider' => $user['provider'],    //$driver,
                    'provider_id' => $user['provider_id'],   //$providerUser->getId(),
                    'verified' => true,
                    'access_token' => $user['access_token'],
                    // user can use reset password to create a password
                    //CONSIDER triggering RESET Password
                ]);
            }else{
                        //This has already been handled in handleProviderCallback's $response... Consider removing after test
                $response = ["error"=>"An email address is not available on your $Provider account", "message"=>"Sorry, you cannot login with $Provider! Kindly try another Social account."];
                return response()->json($response, 400);
            }
        }

        // login the user
    //    Auth::login($user, true);
        $token = $socialUser->createToken('Laravel Password Grant Client')->accessToken;

        return $this->sendSuccessResponse($socialUser, $token);
    }
    
    public function handleProviderCallback(Request $request)    //( $driver ) -- 9f
    {
        //added after commenting out -- 9f
        $user = [
            'email' => $request->email,
            'name' => $request->name,
            'provider' => $request->provider,
            'provider_id' => $request->provider_id,
            'avatar' => $request->photo,
            'access_token' => $request->access_token
        ];
        $Provider = ucfirst($user['provider']);
        //end -- 9f add
    /*
        try {
            $user = Socialite::driver($driver)->user();
        } catch (Exception $e) {
            return response()->json($this->sendFailedResponse($e->getMessage()), 400);
        }
    */
        // check for email in returned user
       // return empty( $user->email )
    ////    $response = empty( $user->email )
        $response = empty( $request->email )
            ? $this->sendFailedResponse("No email id returned from $Provider.")
            : $this->loginOrCreateAccount($user, $Provider);    //($user, $driver);

            return response()->json($response, 200);
    }

    protected function sendSuccessResponse($user, $token)
    {
        //return redirect()->intended('home');
        //$response = redirect()->intended('home');
        $response = [
            'success' => true,
            'message' => 'Login was successful',
            'user' => $user,
            'token' => $token
        ];
        return response()->json($response, 200);
    }

    protected function sendFailedResponse($msg = null)
    {
        return response()->json(
            [
                'success' => false,
                'msg' => $msg ?: 'Unable to login, try with another provider to login.'
            ], 422);
        /*
        return redirect()->route('social.login')
            ->withErrors(['msg' => $msg ?: 'Unable to login, try with another provider to login.']);
        */
    }

}
