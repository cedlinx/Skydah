<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use Validator;
use App\Models\User; 

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles = Role::get(['id', 'role', 'description']); 
        return response()->json([
            $roles->toArray()
        ], 200);
    }

    public function assign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer',
            'role_id' => 'required|integer',
            'email' => 'nullable|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        if ( is_null($request->user_id) && is_null($request->email) )
            return response()->json(['message' => 'The user_id and email fields cannot BOTH be empty. You must provide at least one.']);

        $user = User::where('id', $request->user_id)->orWhere('email', $request->email)->first();
        $user->role_id = $request->role_id;
        
        $updated = $user->save();
        $user = $user->fresh(); //get the current role, etc

        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => 'You have successfully assigned the ' .$user->role->role. ' role to '.$user->name
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, role assignment failed. Please, check your parameters and try again.'
            ]);
        }

    }
    
    public function revoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer',
            'email' => 'nullable|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        if ( is_null($request->user_id) && is_null($request->email) )
            return response()->json(['message' => 'The user_id and email fields cannot BOTH be empty. You must provide at least one.']);

        $user = User::where('id', $request->user_id)->orWhere('email', $request->email)->first();
        $user->role_id = 0; //set user role to Guest... they can no longer perform any crud operation
        
        $updated = $user->save();

        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => "You have successfully revoked " .$user->name. "'s role."
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, the role could not be revoked. Please, check your parameters and try again.'
            ]);
        }
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|max:255',
            'description' => 'nullable|string|max:255'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $role = new Role;
        $role->role = $request->role;
        $role->description = $request->description;
        $role->save();

        return response()->json([
            'success' => true,
            'message' => 'User role created successfully!'
        ], 200);
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
            'id' => 'required|integer',
            'role' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $role = Role::find($request->id);
        if ( ! ($role) ) return response()->json(['Sorry! Role not found.'], 422);
        
        $role->role = $request->role;
        $role->description = $request->description;
        $role->save();
        return response()->json([
            'success' => true,
            'message' => 'The role has been updated!'
        ],200);
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

        $role = Role::find($request->id);
        if ( ! ($role) ) return response()->json(['Sorry! Role not found.'], 422);
        
        $grp = $role->role;
        $role->delete();
        return response()->json([
            'success' => true,
            'message' => 'The ' .$grp. ' role has been deleted!'
        ],200);
    }
}
