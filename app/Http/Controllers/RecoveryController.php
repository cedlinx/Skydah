<?php

namespace App\Http\Controllers;

use App\Models\Recovery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Asset;
use Validator;

class RecoveryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    //    $recoveries = Recovery::all();
        if ($request->has('view')) {
            $recoveries = DB::table('recoveries')
                ->leftJoin('assets', 'assets.id', '=', 'recoveries.asset_id')
                ->leftJoin('users', 'users.id', '=', 'recoveries.user_id')
                ->leftJoin('users as owners', 'owners.id', '=', 'recoveries.owner')
                ->select('recoveries.*', 'assets.name as assetName', 'users.name as founder', 'owners.name as ownerName')
                ->get();
        } else {
            $recoveries = DB::table('recoveries')
                ->leftJoin('assets', 'assets.id', '=', 'recoveries.asset_id')
                ->leftJoin('users', 'users.id', '=', 'recoveries.user_id')
                ->leftJoin('users as owners', 'owners.id', '=', 'recoveries.owner')
                ->select('recoveries.*', 'assets.name as asset-name', 'users.name as founder', 'owners.name as owner-name')
                ->get();
        }
        return response()->json($recoveries, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Recovery  $recovery
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        //$request->id is auth()->user()->id
        if ($request->has('view')) {
            $recoveries = DB::table('recoveries')
                ->leftJoin('assets', 'assets.id', '=', 'recoveries.asset_id')
                ->leftJoin('users', 'users.id', '=', 'recoveries.user_id')
                ->leftJoin('users as owners', 'owners.id', '=', 'recoveries.owner')
                ->select('recoveries.*', 'assets.name as assetName', 'users.name as founder', 'owners.name as ownerName')
                ->where('recoveries.owner', $request->id)
                ->get();
        } else {
            $recoveries = DB::table('recoveries')
                ->leftJoin('assets', 'assets.id', '=', 'recoveries.asset_id')
                ->leftJoin('users', 'users.id', '=', 'recoveries.user_id')
                ->leftJoin('users as owners', 'owners.id', '=', 'recoveries.owner')
                ->select('recoveries.*', 'assets.name as asset-name', 'users.name as founder', 'owners.name as owner-name')
                ->where('recoveries.owner', $request->id)
                ->get();
        }
        return response()->json($recoveries, 200);
        
    }

    public function listFound()
    {
        /* //useful if found assets are appropriately related to assets... right now, that's not guaranteed
        $recoveries = DB::table('recoveries')
        ->leftJoin('assets', 'assets.id', '=', 'recoveries.asset_id')
        ->leftJoin('users as owners', 'owners.id', '=', 'recoveries.owner')
        ->select('recoveries.*', 'assets.name as asset-name', 'owners.name as owner-name')
        ->where('recoveries.lost_but_found', 1)
        ->get();
        */

        $recoveries = Recovery::where('recoveries.lost_but_found', 1)->get();

    return response()->json($recoveries, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Recovery  $recovery
     * @return \Illuminate\Http\Response
     */
    public function reportFound(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asset_description' => 'nullable|string',
            'asset_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'identifier' => 'nullable|string|max:30',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:16',
            'email' => 'nullable|email'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }
            $title = 'Skydah Alert: Found Asset';
            $alert = "It appears someone has found your lost asset. If you lost an asset, kindly request more info from your dashboard";
//            $recipients = $asset->user->phone;
            
            if ( ! is_null(auth()->user() ) ) {
               // dd(auth()->user()->name);
                $name = auth()->user()->name;
                $email = auth()->user()->email;
                $phone = auth()->user()->phone;
                $user_id = auth()->user()->id;
                $recipients = $asset->user->phone;
            } else {
                $name = $request->name;
                $email = $request->email;
                $phone = $request->phone;
                $user_id = null;
                $recipients = $phone;
            }

            //log this in the DB so the original device owner can view it on their dashboard...
            //It'll probably be best to simply log it in the db and only alert users and agencies if/when the asset is flagged as missing
            $recovery = new Recovery;
                $recovery->assetid = $request->identifier;  //any unique ID ... skydahid, assetid, etc
                $recovery->name = $name;    //auth()->user()->id,
                $recovery->email = $email;
                $recovery->phone = $phone;
                $recovery->user_id = $user_id;
                $recovery->location = $request->location;
                $recovery->asset_name = $request->asset_name;
                $recovery->asset_description = $request->asset_description;
                $recovery->lost_but_found = 1;
                //'lat' => $request->lat, //optional here
                //'lng' => $request->lng, //optional here
                //'location' =>$this->getLocation($request->lat, $request->lng)
        
            if ($recovery->save()){// = Recovery::create($recovery) ){

            $this->sendSMS($recipients, $alert);
            $this->sendEmail($asset->user->email, $title, $alert);
                return response()->json([
                    'success' => true, 
                    'message' => 'The found asset is now on Skydah waiting for the owner. Thanks for being so kind.'
                ], 200);
            } else {
                return response()->json([
                    'success' => false, 
                    'message' => 'Oops! the asset could not be submitted. Please, try again'
                ], 500);
            }
    }

    public function getNotifications()
    {
        $notify = Recovery::where('owner', auth()->user()->id)->get('assetid', 'asset_name', 'location', 'name');

        if ( ! $notify ) return response()->json([
            'message' => 'No new notifications!'
        ], 200);

        return response()->json([
            'success' => true,
            'message' => 'An asset that belongs to you has shown up on Skydah! Check your email for details.',
            'data' => $notify,
            'notification_count' => $notify->count()
        ], 200);
    }

}
