<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Seshac\Otp\Otp;
use Illuminate\Auth\Events\Verified;
use App\Models\User;

class OtpController extends Controller
{
    public function getOTP($identifier)
    {
        $otp =  Otp::setValidity(15)  // otp validity time in mins
                    ->setLength(6)  // Lenght of the generated otp
                    ->setMaximumOtpsAllowed(6) // Number of times allowed to regenerate otps
                    ->setOnlyDigits(true)  // generated otp contains mixed characters ex:ad2312
                    ->setUseSameToken(false) // if you re-generate OTP, you will get same token
                    ->generate($identifier);
        
        return $otp;
    }

    public function sendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string|max:255',
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $identifier = $request->identifier;
        $otp = $this->getOTP($identifier);
        $title = 'Skydah OTP';  //dd($otp);
        $alert = 'Your OTP is '.$otp->token. '. It will expire in 15 minutes';
    //    $this->sendSMS(auth()->user()->phone, $alert);    //$this->sendSMS($identifier, $alert);
    //    $this->sendEmail(auth()->user()->email, $title, $alert);
        $this->sendEmail($identifier, $title, $alert);

        if ( ! ($otp->status) )
        return response()->json([
            'success' => false,
            'message' => $otp->message,
        ], 412);

        return response()->json([
            'success' => true,
            'OTP' => $otp->token,
            'message' => 'An OTP has been sent to your registered email. It will expire in 15 minutes.'
        ], 200);
        
    }
 
    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string|max:255',
            'otp' => 'required|string|max:6',
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $identifier = $request->identifier;
        $token = $request->otp;
        $verified = Otp::setAllowedAttempts(2) // number of times they can allow to attempt with wrong token
                    ->validate($identifier, $token);
    
        if ( !($verified->status) )
        return response()->json([
            'success' => false,
            'message' => $verified->message,
        ], 412);

        //token is verified, so mark email as verified
        $user = User::where('email', $identifier)->first(); //->orWhere('phone', $identifier)->first();

        if ( ! $user ) return response()->json(['User not found!'], 404);
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
            $response = 'Thanks! Your email has been verified.';
        } else {
            $response = 'Your email is already verified. Thank you';
        }

        return response()->json([
            'success' => true,
            'message' => $response,
            'user' => $user
        ], 200);

//because we are using this for email verification (NOT OTP validation), it may never get beyond this line
        return response()->json([
            'success' => true,
            'status' => 'OTP Verified!',
            'message' => $verified->message,
        ],200);
    }
}