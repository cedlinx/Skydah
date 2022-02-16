<?php

namespace App\Http\Controllers\Auth;

use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;

//API 
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Auth;



class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function guard()
    {
        return Auth::guard('api');
    }


    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

  
    protected function ShowPasswordForm(Request $request)
    {
        //COA: This function was added to provide the frontend dev the reset password token IF REQUIRED
        $input = $request->only('token');
        $validator = Validator::make($input, [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message'=>$validator->errors()->all()], 422);
        }
        $response = $input;
        return response()->json($response, 200);
    }
   
    //ACTIVE
    protected function sendResetResponse(Request $request){
        //password.reset
        $input = $request->only('email','token', 'password', 'password_confirmation');
        $validator = Validator::make($input, [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['message'=>$validator->errors()->all()], 422);
        }

        $response = Password::reset($input, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->save();

            //$user->setRememberToken(Str::random(60));
            event(new PasswordReset($user));
        });

        if($response == Password::PASSWORD_RESET){
            $message = "Password reset successfully";
        }else{
            $message = "Password reset failed. Please, try again";
        }

        $response = ['data'=>'','message' => $message];
        return response()->json($response);
    }

}


