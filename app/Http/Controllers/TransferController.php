<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Asset;
use Validator;

class TransferController extends Controller
{
    public function index()
    {
        //    $transfers = Recovery::all();
        if ($request->has('view')) {
            $transfers = DB::table('transfers')
                ->leftJoin('assets', 'assets.id', '=', 'transfers.asset_id')
                ->leftJoin('users', 'users.id', '=', 'transfers.user_id')
                ->leftJoin('users as newOwners', 'newOwners.id', '=', 'transfers.newOwner')
                ->select('transfers.*', 'assets.name as assetName', 'users.name as previousOwner', 'newOwners.name as newOwner')
                ->get();
        } else {
            $transfers = DB::table('transfers')
                ->leftJoin('assets', 'assets.id', '=', 'transfers.asset_id')
                ->leftJoin('users', 'users.id', '=', 'transfers.user_id')
                ->leftJoin('users as newOwners', 'newOwners.id', '=', 'transfers.newOwner')
                ->select('transfers.*', 'assets.name as asset-name', 'users.name as previous-owner', 'newOwners.name as new-owner')
                ->get();
        }
        return response()->json($transfers, 200);
    }  

    public function show(Request $request)
    {
        //$request->id is auth()->user()->id
        if ($request->has('view')) {
            $transfers = DB::table('transfers')
                ->leftJoin('assets', 'assets.id', '=', 'transfers.asset_id')
                ->leftJoin('users', 'users.id', '=', 'transfers.user_id')
                ->leftJoin('users as newOwners', 'newOwners.id', '=', 'transfers.newOwner')
                ->select('transfers.*', 'assets.name as assetName', 'users.name as previousOwner', 'newOwners.name as newOwner')
                ->where('transfers.user_id', $request->id)
                ->get();
        } else {
            $transfers = DB::table('transfers')
                ->leftJoin('assets', 'assets.id', '=', 'transfers.asset_id')
                ->leftJoin('users', 'users.id', '=', 'transfers.user_id')
                ->leftJoin('users as newOwners', 'newOwners.id', '=', 'transfers.newOwner')
                ->select('transfers.*', 'assets.name as asset-name', 'users.name as previous-owner', 'newOwners.name as new-owner')
                ->where('transfers.user_id', $request->id)
                ->get();
        }
        return response()->json($transfers, 200);
    }

    public function get_ownership_history(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|integer',
            'skydahid' => 'nullable|string|max:20',
            'assetid' => 'nullable|string|max:50'
        ]);
        if ( $validator->fails() ) {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }
        
        if ( (!$request->id && !$request->skydahid && !$request->assetid ) )
            return response()->json([
                'success' => false,
                'message' => 'Please, provide an ID, a Skydah ID or an Asset ID!'
            ]);

        if ( $request->id ) $id = $request->id;
        if ( $request->skydahid || $request->assetid ) {
            $asset = Asset::where('skydahid', $request->skydahid)->orWhere('assetid', $request->assetid)->get();
            $id  = $asset[0]->id;
        }

        if ($request->has('view')) {
            $history = DB::table('assets')
                ->leftJoin('transfers', 'assets.id', '=', 'transfers.asset_id')
                ->leftJoin('users', 'users.id', '=', 'transfers.user_id')
                ->leftJoin('users as newOwners', 'newOwners.id', '=', 'transfers.newOwner')
                ->select('assets.*', 'users.name as previousOwner', 'newOwners.name as newOwner')
                ->where('assets.id', $id)
                ->get();
        } else {
            $history = DB::table('assets')
                ->leftJoin('transfers', 'assets.id', '=', 'transfers.asset_id')
                ->leftJoin('users', 'users.id', '=', 'transfers.user_id')
                ->leftJoin('users as newOwners', 'newOwners.id', '=', 'transfers.newOwner')
                ->select('assets.*', 'users.name as previous-owner', 'newOwners.name as new-owner')
                ->where('assets.id', $id)
                ->get();
        }
        return response()->json($history, 200);
    }

    
}
