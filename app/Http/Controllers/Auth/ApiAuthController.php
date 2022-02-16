<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

//Email verification
use Illuminate\Auth\Events\Registered;    //This is automatically implemented if you use a Laravel start-kit (like Jetstream)
use Illuminate\Support\Facades\Auth;
use Storage;
use Illuminate\Http\UploadedFile;
//use Smartisan\Settings\Settings;

//use App\Models\Preference;

class ApiAuthController extends Controller
{
    //
    public function register (Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'address' => 'required|string',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
        //    'role' => 'nullable|integer',     //moved, so superadmins can assign roles to their team members
            'group_id' => 'nullable|integer', //moved, so during company registration, users can specify whether they are an Agency or a Business Enterprise... all other users are Individual by default
            'pin' => 'required|string|max:4',      //digits:4',
            'sospin' => 'nullable|string|max:4',  //digits:4',
            'company_code' => 'nullable|string',
            'alternate_phone' => 'nullable|string|min:8|max:12',
            'secondary_email' => 'nullable|email|max:255',
            'photo' => 'nullable|mimes:png,jpg,jpeg,bmp|max:2048',  //avatar
            'mobile' => 'nullable|digits:1'
        //    'avatar' => 'nullable|string|max:255'
        ]);

        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

        $avapath = null;    //Initialize avatar file path in case user doesn't provide a photo
        if($request->file('photo')) {     //then user is providing a photo
            $avadoc = $this->getDocumentHash($request->file('photo'), true);
            $avapath = $avadoc['path'];
            //if we need the photo hash, just use the following
            //$avahash = $avadoc['hash'];
        }

        if($request->has('base64_photo')) {     //then user is providing a photo
            $avadoc = $this->getFileFromBase64($request->base64_photo, $request->photo_ext, true);
            $avapath = $avadoc['path'];
        }

        if ( !( is_null($request['company_code']) ) ) {
            $company = Company::where('code', $request->company_code)->first(); 
            if( !($company) ) return response()->json(['Company Code not found! Please try again'], 422);
            $request['company_id'] = $company->id;
        }

        $request['password']=Hash::make($request['password']);
        $request['remember_token'] = Str::random(10);
        $request['role_id'] = $request['role_id'] ? $request['role_id']  : 0; //During signup, role is not available to the user so... it may as well just be 0
        $request['avatar'] = $avapath;

        if ($request->has('group_id'))
        {
            $group = Group::find($request->group_id);
            if ($group->name == 'Agency' || $group->name == 'Enterprise') {
                $request['role_id'] = 1;
            }
        }



        $user = User::create($request->toArray());
        $user->settings()->group('Security')->set('2fa', 0);    //Disable 2FA by default

        if ( !$request->has('mobile') )     //This line was added to prevent the app from sending verification emails to users who register on mobile devices, as Dayo chose to use OTP verification mail from SendOTP()
        event(new Registered($user)); //Trigger Email verification. This will call the VerifyEmail function specified in the User model. So you can create your own Email Notification and call it from there. I have one here which I used for testing/debugging and it works great too.
    //    $user->sendEmailVerificationNotification();   //This works great as well BUT it is manual unlike using the Registered event
/*
        //Add preferences for the user... defaults are added for the user
        $preference = new Preference;
        $preference->user_id = $user->id;
        $preference->save();
*/
        $token = $user->createToken('Laravel Password Grant Client')->accessToken; //use this to auto-login registered user. They cannot verify their email if they are not authenticated
        $response = [
            'message' => 'Registration was successful! Check your email ('. $request['email'] .') to activate your account',
            'user' => $user,
            'token' => $token,
            'company' => $user->company,
            'group' => $user->group,
            'verified' => is_null($user->email_verified_at) ? false : true,
        ];

        return response()->json($response, 200);
    }

    public function login (Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string',
        ]);

        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }
    
        $user = User::where('email', $request->email)->first();
        if ($user) {           
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Laravel Password Grant Client')->accessToken;

                //If user has not set 2FA, it's probably their first signin, disable 2FA by default for them
                if ( ! ( $user->settings()->exists('2fa')) ) $user->settings()->group('Security')->set('2fa', 0);
                
                //check whether email has been verified
                if (is_null($user->email_verified_at)) {
                    $response = [
                        'message' => 'Please check your email and click on the verification link we sent to you to verify your email.',
                        'remark' => 'User should be able to request another activation link',
                        'data' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'address' => $user->address,
                            'token' => $token, //we are NO LONGER preventing login by unverified emails
                            'verified' => is_null($user->email_verified_at) ? false : true 
                        ]
                    ];
                    return response()->json($response, 401);
                }   //end of verification check

                $response = [                    
                    'message' => 'Login was successful!',
                    'logged_in_user' => $user,  //->name,
                    'token' => $token,
                    'verified' => is_null($user->email_verified_at) ? false : true,
                    'company' => $user->company,
                    'group' => $user->group,
                    'plan' => $user->plan,
                    'active_plan' => $user->plan->name,
                    'plan_type' => $user->plan->account_type,
                    'plan_device_limit' => $user->plan->no_of_devices,
                    'preferences' => $user->settings()->all()
                ];
                return response()->json($response, 200);
            } else {
                $response = ["message" => "Invalid username or password"];  //Password mismatch
                return response()->json($response, 412);
            }
        } else {
            $response = ["message" =>'Invalid username or password'];   //User does not exist
            return response()->json($response, 400);
        }
    
    }

    public function logout (Request $request) {
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response()->json($response, 200);
    }

    public function changePassword(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'current_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['message'=>$validator->errors()->all()], 422);
        }

        $newPass = Hash::make($request->password);
        if ( ! Hash::check($request->current_password, auth()->user()->password) ) 
            return response()->json([
                'success' => false,
                'message' => "Invalid username or password. If you forgot your password, please use the 'Forgot Password' link"
            ], 422);
        
        $user = auth()->user();
        if ( $user->forceFill(['password' => $newPass])->save() ) {
            return response()->json([
                'success' => true,
                'message' => 'Password successfully changed!'
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Oops! Password could not be changed. Please try again'
            ], 500);
        }
    }

    public function showToken() {
        return auth()->user();//->toArray();
    }

    //Handle MethodNotAllowedHttpException
    public function noGet(){
        $response = [
            "Error" => "Method Not Allowed HTTP Exception",
            "message" => "You are using GET instead of POST!"
        ];

        return response()->json($response, 405);
    }

    public function noPost(){
        $response = [
            "Error" => "Method Not Allowed HTTP Exception",
            "message" => "You are using POST instead of GET!"
        ];

        return response()->json($response, 405);
    }

    public function usePut(){
        $response = [
            "Error" => "Method Not Allowed HTTP Exception",
            "message" => "You are using POST instead of PUT!"
        ];

        return response()->json($response, 405);
    }

    public function useDel(){
        $response = [
            "Error" => "Method Not Allowed HTTP Exception",
            "message" => "You are using POST instead of DELETE!"
        ];

        return response()->json($response, 405);
    }

    public function who_is_in() {
        return auth()->user();//->toArray();
    }
}

