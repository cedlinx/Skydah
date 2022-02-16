<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use Validator;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $groups = Group::get(['id', 'name', 'description'])->take(3);    //limit the results to only the top3 as other groups are for internal use only
        return response()->json([
            $groups->toArray()
        ], 200);
    }
                    //$string = preg_replace('/\s+/', '', $string); //remove spaces from a string
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $group = new Group;
        $group->name = $request->name;
        $group->description = $request->description;
        $group->save();

        return response()->json([
            'success' => true,
            'message' => 'User group created successfully!'
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $group = Group::find($request->id);
        if ( ! ($group) ) return response()->json(['Sorry! Group not found.'], 422);
        
        $group->name = $request->name;
        $group->description = $request->description;
        $group->save();
        return response()->json([
            'success' => true,
            'message' => 'The group has been updated!'
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

        $group = Group::find($request->id);
        if ( ! ($group) ) return response()->json(['Sorry! Group not found.'], 422);
        
        $grp = $group->name;
        $group->delete();
        return response()->json([
            'success' => true,
            'message' => 'The ' .$grp. ' group has been deleted!'
        ],200);
    }
}
