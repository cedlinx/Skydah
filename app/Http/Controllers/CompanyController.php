<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Company;
use App\Models\User;
use App\Http\Traits\GetInitials;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;

class CompanyController extends Controller
{
    use GetInitials;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $companies = Company::get(); 
        return response()->json([
            $companies->toArray()
        ], 200);
    }


    public function add_company(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:companies',
            'email' => 'nullable|email',    //use the same email used for creating the user account... it'll become the super user
        //    'group_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $email = $request->email;
        if ( is_null($email) ) $email = auth()->user()->email;

        $data = [
            'name' => $request->name,
            'email' => $email,
        //    'group_id' => $request->group_id,
            'code' => ''
        ];

        $company = Company::create($data);
        $company->code = $this->generate($company->name) . $company->id;
        $company->save();
        
        if($company != null) {
            //$user = User::where('email', $company->email)->update(['group_id' => $company->group_id, 'role_id' => 1]); //update the user registering this company to Company Admin/Company SU and agency/enterprise group
            $user = User::where('email', $company->email)->update(['role_id' => 1]);
            if (! $user) return response()->json(['message' => 'It appears you used different emails for your user and company accounts. It is recommended that you use the email address for a smooth access control experience.'], 201);
        //    return $this->sendSuccess('Company successfully created. Let your Team members provide this company code: '.$company->code. ', during registration', $company);
            return $this->sendSuccess('Company successfully created with company code: '.$company->code, $company);
        } else {
            return $this->sendError('Unable to create company. Please try again', $company = []);
        }
    }

    public function edit_company(Request $request)
    {
        $validator = Validator::make($request->all(), [
        //    'name' => 'required|unique:companies',
            'id' => 'required|integer',
            //'group_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        //$name = $request->name;
        $company_id = $request->id;
        //$group = $group_id;

        if ( auth()->user()->company_id != $company_id  &&  auth()->user()->group_id < 6 )
        return response()->json([
            'success' => false,
            'message' => 'You can only edit your own company unless you are a Skydah admin'
        ], 401);

        $company = Company::find($company_id);

        if($company) {
            $updated = $company->fill($request->all())->save(); 
            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'Company details have been updated.'
                ], 200);
            }

        } else {
            return $this->sendError('Company does not exist', $company = []);
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
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        if ( auth()->user()->company_id != $request->id  &&  auth()->user()->group_id < 6 )
        return response()->json([
            'success' => false,
            'message' => 'You can only delete your own company unless you are a Skydah admin'
        ], 401);

        $company = Company::find($request->id);
        if ( ! ($company) ) return response()->json(['Sorry! Company not found.'], 422);
        
        $coy = $company->name;
        $company->delete();
        return response()->json([
            'success' => true,
            'message' => $coy. ' has been deleted!'
        ],200);
    }

    public function addCompanyUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|min:8|max:12',
            'company_id' => 'nullable|integer',
            'role' => 'required|integer',     //moved, so superadmins can assign roles to their team members
            'group_id' => 'nullable|integer', //moved, so during company registration, users can specify whether they are an Agency or a Business Enterprise... all other users are Individual by default
        ]);

        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        //Assign defaults for company-added user
        $request['password'] = 'Secret';
        $request['pin'] = 1234;
        $request['group_id'] = (auth()->user()->group_id > 5) ? $request['group_id'] : auth()->user()->group_id;
        $request['company_id'] = (auth()->user()->group_id > 5) ? $request['company_id'] : auth()->user()->company_id;
        $request['email_verified'] = 1; //This was converted to represent/differentiate users who were ADDED (value = 1) from those who REGISTERED (value = 0)

        if ( is_null( $request['group_id'] ) ) return response()->json([
            'success' => false,
            'message' => 'Group ID (the group_id field) cannot be null.'
        ], 422);

        $request['password'] = Hash::make($request['password']);

        $request['remember_token'] = Str::random(10);
        //$request['role_id'] = $request['role_id'] ? $request['role_id']  : 0; //During signup, role is not available to the user so... it may as well just be 0
        $request['role_id'] = $request->has('role_id') ? $request['role_id']  : 2;  //Role_id = 2 => Regular user

        $user = User::create($request->toArray());
        event(new Registered($user)); //Trigger Email verification. This will call the VerifyEmail function specified in the User model. So you can create your own Email Notification and call it from there. I have one here which I used for testing/debugging and it works great too.
    //    $user->sendEmailVerificationNotification();   //This works great as well BUT it is manual unlike using the Registered event

        $response = [
            'message' => $request['name']." was successfully added! They should check their email to activate their account. Their default password is 'Secret' and the default PIN is '1234'",
            'data' => [
                'user_id' => $user->id,
                'name' => $request['name'],
                'email' => $request['email'],
            //    'role' => $user->role->name,  //Won't work if the roles table is empty/user has no role assigned (not even a default)
                'verified' => is_null($user->email_verified_at) ? false : true,
            //    'user' => $user
            ] 
        ];

        return response()->json($response, 200);

    }

    public function editCompanyUser(Request $request)
    {
        /*
        Editing a user is complex (avatar, base64 and all), so we will not duplicate the process here.

        Instead, only some basic info can be edited. Major modifications should be done by the user via edit profile

        */

        //INCOMPLETE ... TEST to confirm
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::Find($request->id);

        if($user) {

            if ( ! (auth()->user()->company_id == $user->company_id  ||  auth()->user()->group_id > 5 ) )
                return response()->json([
                'success' => false,
                'message' => 'You can only modify a user in your company, unless you are a Skydah admin'
            ], 401);

            $updated = $user->fill($request->all())->save(); 
            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'User details have been updated.',
                    'data' => $user->fresh()
                ], 200);
            }

        } else {
            return $this->sendError('User does not exist', $user = []);
        }
    }

    public function viewCompanyUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::find($request->id);
        if ( ! $user ) return response()->json([
            'success' => false,
            'message' => 'User not found!'
        ], 201);

        return response()->json([
            'success' => true,
            'data' => $user,
            'assets' => $user->assets
        ], 200);
    }

    public function listCompanyUsers(Request $request)
    {
        $users = User::where('company_id', auth()->user()->company_id)->get(); 
        return response()->json([
            'success' => true,
            'data' => $users
        ], 200);

    }

    public function deleteCompanyUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::find($request->id);
        if ( ! $user ) return response()->json([
            'success' => false,
            'message' => 'User not found!'
        ], 201);

        if ( $user->delete() ) return response()->json([
            'success' => true,
            'message' => $user->name.' has been deleted!'
        ], 200);

        return response()->json([
            'success' => false,
            'message' => 'Oops! User could not be deleted. Please, try again later. '
        ], 500);
    }
} 
