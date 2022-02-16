<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Arr;
use Validator;

class UserController extends Controller
{
    public function danger($email){
        $user = User::where('email', $email)->first();

        if (! ($user) ) {
            return response()->json(["message" => "User not found!"]);
        }
        $newMail = $user->id.$email;
        $user->update(['email' => $newMail]);
        return response()->json(["message"=>"Delete Successful!"], 200);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $allUsers = null;
        User::chunkById(2000, function($users) use(&$allUsers){         
            $allUsers = $users->map(function ($user) {  //Add custom fields to users
                $user['active_plan'] = $user->plan->name;     //$user->plan_id = 0 ? null : $user->plan->name;
                $user['plan_type'] = $user->plan->account_type;       //$user->plan_id = 0 ? null : $user->plan->account_type;
                $user['plan_device_limit'] = $user->plan->no_of_devices;       //$user->plan_id = 0 ? null : $user->plan->no_of_devices;
                return $user;
            });
        });

        if ( is_null($allUsers) ) {
            return response()->json([
                'success' => false,
                'data' => 'No user record found!'
             ]);
        } else {

            return response()->json([
                'success' => true,
                'data' => $allUsers
            ]);
        }
    /*    //This works but may have performance issues when the database get really large
       $users = User::all();
       if( ! ($users) )
            return response()->json([
                'success' => true,
                'message' => 'User record is empty!'
            ]);

       return response()->json([
           'success' => true,
           'data' => $users
        ]);
    */
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //This is handled in the ApiAuthController
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    } 

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        if ( ! ($request->has('user')) )
            return response()->json(
                [
                    "'User' field is required and must be either the user's ID or email address."
                ]
            );
        //used for /find/user
        $ref = $request->user;
        $user = User::where('email', $ref)->orWhere('id', $ref)->first();
    //    $userSettings = $user->settings()->all();
        $twoFa = $user->settings()->get('2fa');
        
        if ( ! ($user) ) return response()->json(['Sorry! User not found.'], 404);
        return response()->json([
            'success' => true,
            'verified' => is_null($user->email_verified_at) ? false : true,
            'data' => $user,
            'company' => $user->company,
            'group' => $user->group,
            'plan' => $user->plan,
            'active_plan' => $user->plan->name,
            'plan_type' => $user->plan->account_type,
            'plan_device_limit' => $user->plan->no_of_devices,
            'preferences' => $user->settings()->all(),  //$userSettings
            '2fa' => $twoFa == 1 ? 'Enabled' : 'Disabled'
        ]);
    }

    public function getUserOwnData(Request $request)
    {
        //used for retrieving user profile data
        $user = User::find(auth()->user()->id);
        $twoFa = $user->settings()->get('2fa');
    //    dd($user->userRole->role);
        if ( ! ($user) ) return response()->json(['Sorry! User not found.'], 404);  //redundant... may NEVER be executed as we're using auth()->user()
        return response()->json([
            'success' => true,
            'verified' => is_null($user->email_verified_at) ? false : true,
            'user_type' => $user->group->name,
            'user_role' => $user->userRole->id = 0 ? 'Guest' : $user->userRole->role,   //Using userRole because "role" (belongsTo relationship in User Model) conflicts with the role field in the users table... consider removing
            'data' => $user,
            'company' => $user->company,
            'group' => $user->group,
            'plan' => $user->plan,
            'active_plan' => $user->plan->name,
            'plan_type' => $user->plan->account_type,
            'plan_device_limit' => $user->plan->no_of_devices,
            'preferences' => $user->settings()->all(),
            '2fa' => $twoFa == 1 ? 'Enabled' : 'Disabled'
        ]);
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }
 
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|integer',  //NOT required since this will be auth()->user() updating his profile. I changed it from required to nullable and still left it so we don't break the mobile app which probably already uses it
            'photo' => 'nullable|mimes:png,jpg,jpeg|max:2048',    //NOT required if sending base64 photo
            'photo_ext' => 'string',    //required only if sending base64 photo
            'base64_photo' => 'string'  //required only if sending base64 photo
        ]);

        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $id = $request->id;
        if($request->file('photo')) {     //then user is changing their profile photo
            $avadoc = $this->getDocumentHash($request->file('photo'), true);
            $request->avatar = $avadoc['dpath'];   //copy the filepath to the request['avatar'] variable
        }

        if($request->has('base64_photo')) {     //then user is providing a photo
            $avadoc = $this->getFileFromBase64($request->base64_photo, $request->photo_ext, true);
            $request->avatar = $avadoc['dpath'];   //$avadoc['path'];
        }
/*
        $user = User::find($id);
 
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry! User could not be found!'
            ], 400);
        }
 */
        $updated = auth()->user()->fill($request->all())->save(); 
        auth()->user()->avatar = $request->avatar;  //needed to properly save image path... does better than preceeding line
        $updated = auth()->user()->save();

        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => 'User details have been updated.',
                'user' => auth()->user()
            ], 200);
        } 
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $ref = $request->user;
        $user = User::where('email', $ref)->orWhere('id', $ref)->first();

        if (! ($user) ) {
            return response()->json(["message" => "User not found!"]);
        }
        $user->delete();
        return response()->json(["message"=>"Delete Successful!"], 200);
    }

    public function destroySelf(Request $request)
    {
        //GDPR Compliance
        //Allow users to delete their user data... this includes their asset data, transaction history and all?
        $id = $request->id;
        $user = User::find($id);
        
    //    $token = $request->user()->token();
    //    $token->revoke();
                       // $user = User::where('email', $request->email)->first();
                       // auth()->login($user);
        //$user = User::find(auth()->user()->id);
        
        //Add code to delete all related data (assets)  //cannot delete related transfers and recoveries
        foreach($user->assets as $asset) {
         // $user->assets()->delete();   //works for bulk deleting user's assets
            $txnID = $this->setValidity($asset->id, false);
            $asset->deletion_txn_id = $txnID;
            $asset->save();
            $asset->delete();
        }

        $token = $request->user()->token();
        $token->revoke();
        $user->delete();

        return response()->json([
            "success" => true,
            "message" => "Your account has been deleted! And you have been logged out. It is sad to see you go. We hope you signup again soon."
        ]);
    }
}
