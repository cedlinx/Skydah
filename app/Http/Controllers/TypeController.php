<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Type;
use Validator;

class TypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $types = Type::get();
        return response()->json([
            $types->toArray()
        ], 200);
    }

    public function dropdown()
    {
        $types = Type::get();
        return response()->json(['types' =>
            $types
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
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
            'type' => 'required|string|max:255',
            'description' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 412);

        $type = new Type;
        $type->type = $request->type;
        $type->description = $request->description;
        $type->save();

        return response()->json([
            'success' => true,
            'message' => 'Asset type created successfully!'
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
            'type' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'id' => 'required|integer'
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 412);

        $type = Type::find($request->id);

        if ( ! ($type) ) return response()->json(['Sorry! Requested asset type not found.'], 422);

        $typeName = $type->type;

        $type->type = $request->type;
        $type->description = $request->description;
        $type->save();
        
        return response()->json([
            'success' => true,
            'message' => 'The asset type has been updated!'
        ]);
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
            'id' => 'required|integer'
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 412);

        $type = Type::find($request->id);

        if ( ! ($type) ) return response()->json(['Sorry! Requested asset type not found.'], 422);

        $typeName = $type->type;

        $type->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'The ' .$typeName. ' asset type has been deleted!'
        ]);
        
    }
}
