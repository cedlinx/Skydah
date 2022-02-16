<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\User;
use Validator;

class SettingController extends Controller
{
    function set2fa(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|boolean',
        ]);

        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

        $action = $request->value == 1 ? 'Enabled' : 'Disabled';
        auth()->user()->settings()->group('Security')->set('2fa', $request->value); //this does not return a true/false response so can we tell whether the operation was successful or not?

        return response()->json([
            'success' => true,
            'message' => 'You have successfully '.$action. ' 2FA'
        ], 200);
        
    }

    function addBooleanSetting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
            'value' => 'required|boolean',
            'group' => 'required|string'
        ]);

        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

        $group = is_null($request->group) ? 'General' : $request->group; //redundant
        $action = $request->value == 1 ? 'Enabled' : 'Disabled';
        auth()->user()->settings()->group($group)->set($request->key, $request->value); //this does not return a true/false response so can we tell whether the operation was successful or not?

        return response()->json([
            'success' => true,
            'message' => 'You have successfully '.$action. ' ' . $request->key
        ], 200);
        
    }

    function addSetting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
            'value' => 'required|string',   //What if this is Numeric/Integer
            'group' => 'required|string'
        ]);

        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

        $group = is_null($request->group) ? 'General' : $request->group; //is now redundant as I've made group Required
    //    $action = $request->value == 1 ? 'Enabled' : 'Disabled';
        auth()->user()->settings()->group($group)->set($request->key, $request->value); //this does not return a true/false response so can we tell whether the operation was successful or not?

        return response()->json([
            'success' => true,
            'message' => 'You have successfully modified setting for ' . $request->key
        ], 200);
        
    }

}
