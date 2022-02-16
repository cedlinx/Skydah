<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class CustomVerificationController extends Controller
{
    public function customVerify(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'verification_token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()->all()], 422);
        }

        $user = User::where('verification_token', $request->verification_token)->first();
        if ( ! $user ) return response()->json([
            'success' => false,
            'message' => 'User not found!'
        ], 401);

        $minutesElapsed = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $user->updated_at)->diffInMinutes(\Carbon\Carbon::now()); //dd($expiryTime);

        if ( $minutesElapsed > 15 ) return response()->json([
            'success' => false,
            'message' => 'Your Email Verification Link has expired. Please, request another one. Validity is 15 minutes.'
        ], 401);
        
        if ( $request->verification_token !== $user->verification_token ) {
            $response = [
                'message' => 'Sorry! It seems you are using an invalid link. Please, try again.',
                'success' => false
            ];
            $code = 401;
    
            return response()->json($response, $code);
        }

        if (!$user->hasVerifiedEmail()) {   //abort(401);

            $user->markEmailAsVerified();
            event(new Verified($user));
            $response = [
                'message' => 'Thanks! Your email has been verified.',
                'data' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'id' => $user->id
                ]
            ];
            $code = 200;

        } else {   // abort(401);
        $response = [
            'message' => 'Your email is already verified. Thank you',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'id' => $user->id
            ]
        ];
        $code = 400;
    }

    return response()->json($response, $code);
    }

}
