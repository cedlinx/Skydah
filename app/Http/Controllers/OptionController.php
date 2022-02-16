<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Option;

use Validator;

class OptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $options = Option::get(); 
        return response()->json([
            $options->toArray()
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
            'setting_key' => 'required|string|max:255',
            'group' => 'required|string|max:255',
            'type' => 'required|string|max:255'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

        $option = new Option;
        $option->setting_key = $request->setting_key;
        $option->group = $request->group;
        $option->type = $request->type;
        $option->save();

        return response()->json([
            'success' => true,
            'message' => 'Option created successfully!'
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
            'setting_key' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

        $option = Option::find($request->id);
        if ( ! ($option) ) return response()->json(['Sorry! Option not found.'], 422);
        
        $updated = $option->fill($request->all())->save();

        if ( ! $updated )  return response()->json(['message' => 'The option could not be updated!'],500);

        return response()->json([
            'success' => true,
            'message' => 'The option has been updated!'
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

        $option = Option::find($request->id);
        if ( ! ($option) ) return response()->json(['Sorry! Option not found.'], 422);
        
        $grp = $option->setting_key;
        $option->delete();
        return response()->json([
            'success' => true,
            'message' => 'The ' .$grp. ' option has been deleted!'
        ],200);
    }

}
