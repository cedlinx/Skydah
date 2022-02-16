<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Config;
use Validator;


class ConfigController extends Controller
{
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    //    dd($this->config('authentication', true, true));
        $settings = Config::all();    //limit the results to only the top3 as other groups are for internal use only
        return response()->json([
            $settings->toArray()
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {

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
            'setting' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'group' => 'nullable|string|max:255'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $setting = new Config;
        $setting->setting = $request->setting;
        $setting->value = $request->value;
        $setting->group = $request->has('group') ? $request->group : null;
        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Setting added successfully!',
            'data' => $setting
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
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
            'id' => 'required|integer',
            'setting' => 'nullable|string|max:255',
            'value' => 'nullable|string|max:255',
            'group '=> 'nullable|string|max:255'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $id = $request->id;
        $setting = Config::find($id);
 
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry! Setting could not be found!'
            ], 400);
        }
 
        $updated = $setting->fill($request->all())->save();   
 
        if ($updated)
        {
            return response()->json([
                'success' => true,
                'message' => 'The Setting has been updated!',
                'data' => $setting
            ],200);
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

        $setting = Config::find($request->id);
        if ( ! ($setting) ) return response()->json(['Sorry! Group not found.'], 422);
        
        $sett = $setting->setting;
        $setting->delete();
        return response()->json([
            'success' => true,
            'message' => 'The ' .$sett. ' group has been deleted!'
        ],200);
    }

    public function enable2fa(Request $request)
    {
        
    }
}
