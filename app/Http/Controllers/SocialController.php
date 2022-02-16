<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Social;
use App\Models\User;

use Validator;

class SocialController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $socials = Social::get(); 
        return response()->json([
            $socials->toArray()
        ], 200);
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
            'provider' => 'required|string|max:255',
            'imageurl' => 'required|string|max:255'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

        $social = new Social;
        $social->provider = $request->provider;
        $social->imageurl = $request->imageurl;
        $social->save();

        return response()->json([
            'success' => true,
            'message' => 'Social created successfully!'
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
            'provider' => 'nullable|string|max:255',
            'imageurl' => 'nullable|string|max:255'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

        $social = Social::find($request->id);
        if ( ! ($social) ) return response()->json(['Sorry! Social not found.'], 422);
        
        $updated = $social->fill($request->all())->save();

        if ( ! $updated )  return response()->json(['message' => 'The social could not be updated!'],500);

        return response()->json([
            'success' => true,
            'message' => 'The social has been updated!'
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

        $social = Social::find($request->id);
        if ( ! ($social) ) return response()->json(['Sorry! Social not found.'], 422);
        
        $grp = $social->provider;
        $social->delete();
        return response()->json([
            'success' => true,
            'message' => $grp. ' has been deleted!'
        ],200);
    }

    public function linkSocials(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'socials' => 'required',    //|array',  //an array socials IDs
    //        'user_id' => 'required|integer'
        ]);

        if (is_string ($request->socials))
        $request->socials = explode(',', $request->socials);    //if socials is posted as a comma separated string, use this to convert it to an array

        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

    //    $user = User::find($request->user_id);
        $socials = Social::find($request->socials);
    //    $user->socials()->sync($socials, false);     //->attach($socials); //attach works well but allows multiple duplicate entries. sync disallows duplicates, the FALSE param syncs without DETACHING
        
        auth()->user()->socials()->sync($socials, false);     //->attach($socials); //attach works well but allows multiple duplicate entries. sync disallows duplicates, the FALSE param syncs without DETACHING
        return response()->json([
            'success' => true,
            'message' => 'Social accounts have been successfully linked',
            'socials' => auth()->user()->socials
        ], 200);
/*
        return response()->json([
            'success' => false,
            'message' => 'Social accounts could not be linked. Please, try again.'
        ], 500);
*/
    }

    public function unlinkSocials(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'socials' => 'required',    //|array',  //an array socials IDs
    //        'user_id' => 'required|integer'
        ]);

        if (is_string ($request->socials))
        $request->socials = explode(',', $request->socials);    //if socials is posted as a comma separated string, use this to convert it to an array

        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

    //    $user = User::find($request->user_id);
        $socials = Social::find($request->socials);
    //    $user->socials()->sync($socials, false);     //->attach($socials); //attach works well but allows multiple duplicate entries. sync disallows duplicates, the FALSE param syncs without DETACHING
        
        auth()->user()->socials()->detach($socials);     
        return response()->json([
            'success' => true,
            'message' => 'Social accounts have been successfully unlinked',
            'socials' => auth()->user()->socials
        ], 200);
    }
}
