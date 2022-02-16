<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Validator;
use App\Models\User;    //check?
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;

//for TESTING EMAIL PDF SENDING STANDALONE
use Mail; 
use PDF; 

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails, HasApiTokens;

    /**
     * Create a new controller instance.
     *
     * @return void
     */



     public function __construct()
    {
        $this->middleware('guest');
    }


    protected function sendResetLinkResponse(Request $request)
    {
        $input = $request->only('email');
        $validator = Validator::make($input, [
        'email' => "required|email"
        ]);

        if ($validator->fails()) {
            return response()->json(['message'=>$validator->errors()->all()], 422);
        }

        $response =  Password::sendResetLink($input);
        //$response = $this->broker()->sendResetLink($input);     //works great as well
        
        if($response == Password::RESET_LINK_SENT){
            $message = "Password Reset mail has been sent successfully to ".$input['email'];
            $code = 200;
        }else{
            $message = "Email could not be sent to ".$input['email'];
            $code = 401;
        }

        $response = ['message' => $message];
        return response()->json($response, $code);
    }

}
