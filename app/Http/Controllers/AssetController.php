<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Asset;
use App\Models\CompanyCode;
use Mail;
//use App\Mail\MyTestMail;

//File Hashing and Uploads
//use App\Http\Requests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr; //used in the show() method to add blockchain data to local data
use App\Image;
use App\Filename;

use Illuminate\Support\Facades\DB;

use Storage;
//use Illuminate\Http\UploadedFile;   //moved to Controller.php along with the getDocumentHash method

use App\Models\User;
use App\Models\Recovery;
use App\Models\Transfer;
use Carbon\Carbon;
use Spatie\Geocoder\Geocoder;
use Seshac\Otp\Otp;

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AssetsImport;
use App\Notifications\AssetRecoveryNotification;


class AssetController extends Controller
{
    //REMOVE THIS CONSTRUCT IN PRODUCTION
    public function __construct()
    {
    /*
        if ( ! auth()->user()){
            $user = User::where('email', 'cedlinx@yahoo.com')->first();
            auth()->login($user);
        //    $this->actingAs($user, 'api');
        }
    */
    }
/*
    public function getTestUser()
    {
    //    $user = User::where('email', 'cedlinx@yahoo.com')->first();
    //    auth()->login($user);
     //   return $user;
    }
*/
    public function index()
    {  // $res = $this->getDataScope();
      //  dd($res);
        $user = auth()->user();
        //Retrieve and display all assets belonging to the logged in user
        $assets = Asset::where('user_id', $user->id)->get();

        //attempt to join types table so we can also retrieve asset types ... currently returns null
        //$assets = Asset::join('types', 'assets.type_id', '=', 'types.id')->where('user_id', $user->id)->get('assets.*', 'types.type', 'types.description');

       //ADD Category & Status to each item of the assets collection
        $assets->map(function ($asset) {
            $asset['category'] = $asset->type->type;
            $asset['status'] = is_null( $asset->flagged_as_lost_at ) ? 'Okay' : 'Lost or Stolen';
            $asset['status_message'] = is_null( $asset->flagged_as_lost_at ) ? 'Asset is safe & intact' : 'Asset has been reported lost or stolen';
            return $asset;
        });

        $assetCount = $assets->count();

        //retrieve auth user's assets from the blockchain
        $bcAssets = $this->getAssetByOwner($user->id);
 
        //merge localdata and blockchain data
        $assets = Arr::add($assets, 'blockchain', $bcAssets); //REMOVED until blockchain starts returning ONLY auth()->user()'s assets
        return response()->json([
            'success' => true,
            'assets' => $assets,
            'asset_count' => $assetCount      
        //    'status' => is_null($assets->flagged_as_lost_at) ? 'OK' : 'Lost or Stolen'
        ], 200);
    }

    //public function show($ref)    //works for a GET
    public function show(Request $request)
    {   
        //Query Skydah for a specific asset using either the assetid or the skydahid
        
        $validator = Validator::make($request->all(), [
            'asset_id' => 'required|string|max:255',
            'lat' => 'required|string', //numeric|between:-90.000000, 90.000000',
            'lng' => 'required|string', //numeric|between:-180.000000, 180.000000',
        //    'address' => 'nullable|string|max:255'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

        $asset = Asset::where('skydahid', $request->asset_id)->orWhere('assetid', $request->asset_id)->first();

        if (!$asset) {
            //notify asset owner

            return response()->json([
                'success' => false,
                'message' => 'Asset not found! Please secure your asset by registering it on Skydah.'
            ], 400);
        }
        
        //Notify asset owner
        if ( ! is_null(auth()->user() ) ) { //then the verifier is a logged in user
            $secondary_owner = auth()->user()->id;
        } else { //the verifier is a guest user
            $secondary_owner = null;
        }
        
        if ($asset->user_id == $secondary_owner) {
            $alert = "We noticed that you just verified your ". $asset->name ." on Skydah. If you lost this asset, kindly go to your dashboard and flag it as missing. Otherwise, someone else may have access to your Skydah credentials and your asset's ID.";
        } else {
            $alert = "Someone has just verified your ". $asset->name ." on Skydah. If you lost this asset, kindly request more info from your dashboard";
        }

        $title = 'Skydah Alert: Possible Recovery';
        $recipients = $asset->user->phone;
        
        $recoveryData = [
            'asset_id' => $asset->id,
            'user_id' => $secondary_owner,    //auth()->user()->id,
            'owner' => $asset->user_id,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'location' => $this->getLocation($request->lat, $request->lng)
        ];

        $recovery = Recovery::create($recoveryData);

        $data['subject'] = "Asset Verification Alert";
        $data['message'] = "Someone just verified your asset: " . $asset->name . ", at " . $recoveryData['location'] ."!";
        $assetOwner = $asset->user;
        $this->sendNotification($assetOwner, $data, $title, $alert);
/*
Moved this block to sendNotification() below
        //If user has enabled notifications
        if ( $asset->user->allows_notification )
        {  
            //Save to notifications table
         //   $notipient = User::find($asset->user_id);
            $data['subject'] = "Asset Verification Alert";
            $data['message'] = "Someone just verified your asset: " . $asset->name . ", at " . $recoveryData['location'] ."!"; 

            //$notipient->notify(new AssetRecoveryNotification($data));
            $asset->user->notify(new AssetRecoveryNotification($data));
        }        
        
        $this->sendSMS($recipients, $alert);
        $this->sendEmail($asset->user->email, $title, $alert);
        //$this->coaSendEmail($asset->user->email, $title, $asset->user->name); //works 
*/
        //retrieve asset from blockchain
        $bcAsset = hash("sha256", $asset->skydahid);    //whether the user specified assetid or skydahid, skydahid will be used to query the blockchain because it is most likely to always be unique
        $res = $this->getAssetBySkydahId($bcAsset, $bcAsset);   //array

        $assetStatus = $asset->asset_status;
        $assetMsg = $asset->asset_status_message;
        $assetCategory = $asset->type->type;    //$asset->asset_category;

        $asset = $asset->toArray();

        $asset = Arr::add($asset, 'blockchain', $res);
        return response()->json([
            'success' => true,
            'asset_status' => $assetStatus,
            'asset_status_message' => $assetMsg,
            'asset_category' => $assetCategory,
            'data' => $asset    //->toArray()
        ], 200);
    }

    //GPS LOCATION CURRENTLY DOES NOT CHANGE WITH UPDATES ... DB only hold initial asset registration location ... REVIEW??
    public function update(Request $request)
    {
        //    $this->sendOTP(auth()->user()->phone);
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

        $id = $request->id;
        $asset = auth()->user()->assets()->find($id);
 
        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry! Asset could not be found!'
            ], 400);
        }
 
        $updated = $asset->fill($request->all())->save();   
 
        if ($updated)
        {
            $updateTxn = $this->setValidity($id, false);
            $asset = $asset->fresh(); //retrieve updated data

            /*  //This works well BUT I thought to place after forming newAsset 
            if ($asset->delete()) { //delete the record and recreate it... UNNECESSARY BUT blockchain recreation would have ID conflict
                //update txnID on our database
                $asset->deletion_txn_id = $updateTxn;
                $asset->save();
            }
*/
            $newAsset = [   //local db
                'name' => $asset->name,
                'description' => $asset->description,
                'type_id' => $asset->type_id,  // 'type_id' => $request->type_id //Frontend devs need to change type_id to category_id
                'assetid' => $asset->assetid,
                'skydahid' => $this->generate_random_string(),            
                'user_id' => $asset->user_id,
                'company_id' => $asset->company_id,
                'transferable' => $asset->transferable   //$request->user_id,
            ];

            if ($asset->delete()) { //delete the record and recreate it... UNNECESSARY BUT blockchain recreation would have ID conflict
                //update txnID on our database
                $asset->deletion_txn_id = $updateTxn;
                $asset->save();
            }

            $newData = Asset::create($newAsset); //try ... catch
        
            //create a new entry on blockchain    
            $newEntry = [       //blockchain   
                'asset_id' => $newData->id, //primary key from pgsql/mysql database
                'asset_skydah_id' => $asset->skydahid,   //'sky-cungnuire8u8fcv8dhvd', //Asset skydah ID (12-16 char alphanumeric string)
                'asset_type' => $asset->name, //Asset type , can be assigned from a list of constants plus asset model
                'asset_type_id' => $asset->type_id, //Type of ID associated with asset - IMEI, serial,...
                'asset_hash' => $asset->assetid, //message digest for this asset. passing a message digest is also recommended
                'asset_skydah_owner' => $asset->user_id,// Current owner of asset
                'asset_transferable' => $asset->transferable
            ];
            $insertTxn = $this->createAsset($newEntry); //save on blockchain //try ... catch

            if($insertTxn){
                $newData->transactionId = $insertTxn;
                $newData->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Asset details have been updated.'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Asset could not be updated on the blockchain!'
                ], 500);
            }
                        /*
                        //This try --- catch is not required here. Remember to delete
                try {
                    $user = User::findOrFail($request->input('user_id'));
                } catch (ModelNotFoundException $exception) {
                    return back()->withError($exception->getMessage())->withInput();
                }

                        return response()->json([
                            'success' => true,
                            'message' => 'Asset details have been updated.'
                        ], 200);    */
        } else
            return response()->json([
                'success' => false,
                'message' => 'Asset could not be updated!'
            ], 500);
    }

    public function destroy(Request $request)
    {
    //$user = $this->getTestUser();
        $id = $request->id;
        $asset = auth()->user()->assets()->find($id);
 
        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset could not be found!'
            ], 400);
        }
 
        if ($asset->delete()) {

            //invalidate on blockchain
            $txnID = $this->setValidity($id, false);

            //update txnID on our database
            $asset->deletion_txn_id = $txnID;
            $asset->save();

            return response()->json([
                'success' => true,
                'message' => 'Asset was successfully deleted!'
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Asset could not be deleted!'
            ], 500);
        }
    }



//Kenny 
    public function add_asset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assetid' => 'nullable|string|max:255',
            'type_id' => 'required|integer',
            'file' => 'nullable|mimes:png,jpg,jpeg,csv,txt,xlsx,xls,doc,docx,ppt,pptx,mp3,mp4,pdf|max:2048',
            'transferable' => 'nullable|integer|min:0|max:1',
            'receipt' => 'nullable|mimes:png,jpg,jpeg,pdf|max:2048',
            'lat' => 'required|string', //numeric|between:0.000000, 90.000000',
            'lng' => 'required|string', //numeric|between:0.000000, 180.000000',
            'company_id' => 'nullable|integer',
            'pin' => 'required|integer'
    //        'address' => 'nullable|string|max:255'

        ]);
        
        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

        $rcppath = null;    //Initialize receipt file path in case user doesn't provide a receipt
        if($request->file('receipt')) {     //then user is providing a receipt           
            $rcpt = $request->file('receipt');
            $rcpdoc = $this->getDocumentHash($rcpt);
            $rcppath = $rcpdoc['path'];          
        }

        if ($request->has('base64_receipt')) { //input is from mobile and therefore will be base64
            $rcpt = $request->base64_receipt;
            $rcpt = $this->getFileFromBase64($rcpt, $request->receipt_ext);
            $rcpdoc = $this->getDocumentHash($rcpt);
            $rcppath = $rcpdoc['path']; 
        }

        if($request->file('file')) {
            $document = $this->getDocumentHash($request->file('file'));
            $hash = $document['hash'];
            $filepath = $document['path'];
            //when ADDING Assets, user cannot provide skydahid so we can only query with assetid or document hash
            $asset = Asset::where('assetid', $request->assetid)->orWhere('hash', $hash)->first();
        } 

        elseif ($request->has('base64_asset')) { //input is from mobile and therefore will be base64
            $asst = $request->base64_asset;
            $asst = $this->getFileFromBase64($asst, $request->asset_ext);
            $hash = $asst['hash'];
            $filepath = $asst['path'];
            $asset = Asset::where('assetid', $request->assetid)->orWhere('hash', $hash)->first();
        } else {
            $asset = Asset::where('assetid', $request->assetid)->first();
            $hash = $filepath = null;
        }

       //could further filter with asset_type as additional where clause... but filtering by asset type will create a hack that will allow users register an already registered asset simply by changing the type
       // $asset = Asset::where('assetid', $request->assetid)->orWhere('hash', $hash)->first(); 
        if ($asset) {
            $title = 'Skydah Alert: Possible Recovery';
            $alert = "It appears someone is trying to register your asset ( ". $asset->name ." ) on Skydah. If you lost this asset, kindly request more info from your dashboard";
            $recipients = $asset->user->phone;
            
            $secondary_owner = auth()->user()->id;

            //log this in the DB so the original device owner can view it on their dashboard...
            //It'll probably be best to simply log it in the db and only alert users and agencies if/when the asset is flagged as missing
            $recoveryData = [
                'asset_id' => $asset->id,
                'secondary_owner' => $secondary_owner,    //auth()->user()->id,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'location' => $this->getLocation($request->lat, $request->lng)
            ];
            $recovery = Recovery::create($recoveryData);

            $this->sendSMS($recipients, $alert);
            $this->sendEmail($asset->user->email, $title, $alert);

            return response()->json([
                'success' => false, 
                'message' => 'An asset with this ID already exists! If you believe this is an error, please, press the Notify button to let us know.' //Add a notify button here for better UX
            ], 422);
        }

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'type_id' => $request->type_id,  // 'type_id' => $request->type_id //Frontend devs need to change type_id to category_id
            'assetid' => $request->assetid,
            'skydahid' => $this->generate_random_string(),            
            'user_id' => auth()->user()->id,
            'transferable' => $request->transferable,   //$request->user_id,
            'hash' => $hash,
            'file' => $filepath,
            'location' => $request->address,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'receipt' => $rcppath,
            'company_id' => auth()->user()->company_id,
            'location' => $this->getLocation($request->lat, $request->lng)
        ];

        $asset = Asset::create($data);
        
        if($asset) 
        {
            $message = "Asset successfully created!"; $code = 200;
            $messageData[1] = $message;

            //Push to block chain
            if( ! ($hash) || is_null($hash) ) $hash =  $data['assetid'];
            $bcData = [         
                'asset_id' => $asset->id, //primary key from pgsql/mysql database
                'asset_skydah_id' => $data['skydahid'],   //'sky-cungnuire8u8fcv8dhvd', //Asset skydah ID (12-16 char alphanumeric string)
                'asset_type' => $data['name'], //Asset type , can be assigned from a list of constants plus asset model
                'asset_type_id' => $data['type_id'], //Type of ID associated with asset - IMEI, serial,...
                'asset_hash' => $hash, //message digest for this asset. passing a message digest is also recommended
                'asset_skydah_owner' => $data['user_id'],// Current owner of asset
                'asset_transferable' => $data['transferable']
            ];
            $bcResult = $this->createAsset($bcData); //save on the blockchain and return a txnID
            if ($bcResult) $message = "Asset added to blockchain"; else  $message = "Asset could not be added to Blockchain!";
            $messageData[2] = $message;

            $newAsset = Asset::find($asset->id);
            if ($newAsset) {
                $newAsset->transactionId = $bcResult; 
                $newAsset->save();//add the blockchain txnID to the newly created asset
                $message = "Transaction ID successfully saved!";    $messageData[3] = $message;
                $code = 200;
            } else {
                $message = "Asset was registered successfully on Skydah & on the BlockChain, but the transaction ID could not be saved.";
                $code = 500;
                $messageData[3] = $message;
                return response()->json($message, $code);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Congrats! Your asset is now protected by Skydah.',
                'data' => $asset->toArray(),
                'track' => $messageData
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Asset could not be added! Please, try again.',
                'track' => $messageData
            ], 500);
        }
 
    }

    public function generate_company_codes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number_of_codes' => 'required',
            'user_id' => 'nullable|integer',
        ]);

        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }
        
        $number_of_codes = $request->number_of_codes;
        $user_id = $request->user_id;

        $codes = array();

        for($i=1; $i<=$number_of_codes; $i++) {
            $data = [
                'user_id' => $request->user_id,
                'code' => $this->generate_random_string()
            ];

            CompanyCode::create($data);
            array_push($codes, $data);
        }

        return $this->sendSuccess('Codes successfully generated', $codes);
    }

    public function get_company_codes($user_id)
    {
        $company_codes = CompanyCode::where('user_id', $user_id)->get();

        if($company_codes != null or $company_codes != NULL) {
            return $this->sendSuccess('Company code successfully retrieved', $company_codes);
        } else {
            return $this->sendError('No compamy code found', $company_codes = []);
        }
    }

    public function generate_random_string($length = 15) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function upload_bulk_assets(Request $request)
    {
        $file = $request->file('file');

        // File Details 
        $filename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $tempPath = $file->getRealPath();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();
  
        // Valid File Extensions
        $valid_extension = array("csv");
  
        // 2MB in Bytes
        $maxFileSize = 2097152; 
  
        // Check file extension
        if(in_array(strtolower($extension),$valid_extension)){
  
          // Check file size
          if($fileSize <= $maxFileSize){
  
            // File upload location
            $location = 'uploads';
  
            // Upload file
            $file->move($location,$filename);
  
            // Import CSV to Database
            $filepath = public_path($location."/".$filename);
  
            // Reading file
            $file = fopen($filepath,"r");
  
            $importData_arr = array();
            $i = 0;
  
            while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
               $num = count($filedata );
               
               // Skip first row (Remove below comment if you want to skip the first row)
               if($i == 0){
                  $i++;
                  continue; 
               }
               for ($c=0; $c < $num; $c++) {
                  $importData_arr[$i][] = $filedata [$c];
               }
               $i++;
            }
            fclose($file);
  
            $assets = [];

            // Insert to MySQL database
            foreach($importData_arr as $importData){

                $data = [
                    'name' => $importData[0],
                    'description' => $importData[1],
                    'skydahid' => $this->generate_random_string(),
                ];

                array_push($assets, $data);

                Asset::create($data);
            }
            return $this->sendSuccess('Asset successfully created', $assets);
          }else{
            return $this->sendError('File too large. File must be less than 2MB.', $assets = []);
          }

        }else{
           return $this->sendError('Invalid File Extension', $assets = []);
        }
  
    }
    
    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transferTo' => 'required|email',
            'id' => 'required|integer',
            'transferReason' => 'nullable|string|max:255',
            'pin' => 'required|integer'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

        //Upon successful transfer, apply softdelete on the previous owner in blockchain
        $asset = auth()->user()->assets->find($request->id);

        if( !($asset) ){
            return response()->json(['message' => 'Asset not found!'], 404);
        }

        $newOwner = User::where('email', $request->transferTo)->get(['id', 'name']);
        if ( !($newOwner) ) return response()->json([
            'success' => false,
            'message' => 'Intended recipient not found! Please, let the recipient first register on Skydah.'
        ]);
        
        $transferData = [
            'user_id' => auth()->user()->id,
            'newOwner' => $newOwner[0]['id'],
            'asset_id' => $request->id,
            'transferReason' => $request->transferReason
        ];

        $transfer = Transfer::create($transferData);

        if ( ! ($transfer) ) return response()->json(['message' => 'Transfer failed! Please try again.'], 422);
        
        //Change asset's user_id to the new owner's
        $asset->user_id = $newOwner[0]['id'];    //$request->transferTo;
        $asset->save();
        $message = 'Asset has been fully transferred! Forwarding to the blockchain...';
        
        //effect transfer on the blockchain        
        $txnID = $this->transferAsset($request->id, $newOwner[0]['id']); //$request->transferTo);

        //add transaction ID to the transferred record
        $transfer->transactionId = $txnID;
        $transfer->save();

    //    $newOwner = User::find($request->transferTo);

        return response()->json(['message' => 'You have successfully transferred '.$asset->name.' to '.$newOwner[0]['name']], 200);
    }

    public function bulkTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:csv,xlsx,xls|max:2048'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

        $csvFile = $request->file('file');

        if ( ! Excel::import(new AssetsImport, $csvFile) ) {
            return response()->json([
                'success' => false,
                'message' => 'Assets could not be imported! Please, try again.'
            ], 500);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Bulk Assets Transfer Was Successful'
        ], 200);
    }

    public function flagAssetAsMissing(Request $request)
    {
        //This flags already registered assets as missing/lost
        //Display select details of "recoveries" on reporting user's dashboard if their flagged item is found
        $validator = Validator::make($request->all(), [
            'skydahid' => 'required|string|max:255',
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

        $id = $request->skydahid;
        $asset = Asset::where('skydahid', $id)->first();
        if( ! ($asset) || is_null($asset) ) return response()->json(['Sorry! Asset not found'], 422);
        if( !( is_null($asset->flagged_as_lost_at) ) ) return response()->json(['This asset has already been flagged as missing!'], 422);
        $asset->flagged_as_lost_at = Carbon::now();
        $asset->save();
//dd($asset->type->pluck('type')[0]);
        return response()->json([
            "success" => true,
            "message" => "We're sorry you lost your ".$asset->name." It has been flagged as missing! If it shows up on Skydah, you'd be notified!",
            "data" => $asset->toArray()
        ], 200);

    }
    
    public function listMissingAssets()
    {
        //This can be modified to list missing assets based on arbitrary critera or have a new fxn... like foe the admin panel
        $user = auth()->user();
        $asset = Asset::where('user_id', $user->id)->whereNotNull('flagged_as_lost_at')->get();  
        if( ! ($asset) || is_null($asset) ) return response()->json(['Hurray! You have not flagged any asset as missing.'], 201);
        return response()->json([
            "success" => true,
            "data" => $asset->toArray(),
            'missing_asset_count' => $asset->count()
        ], 200);

    }

    public function flagAssetAsFound(Request $request)
    {
        //This flags already registered assets as found/recovered
        //Display select details of "recoveries" on reporting user's dashboard if their flagged item is found
        $validator = Validator::make($request->all(), [
            'skydahid' => 'required|string|max:255',
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['message'=>$validator->errors()->all()], 412);
        }

        $id = $request->skydahid;
        $asset = Asset::where('skydahid', $id)->first();
        if( ! ($asset) || is_null($asset) ) return response()->json(['Sorry! Asset not found'], 422);
        if( !( is_null($asset->flagged_as_found_at) ) ) return response()->json(['This asset has already been flagged as found!'], 422);
        if( !( is_null($asset->flagged_as_lost_at) ) ) $asset->flagged_as_lost_at = null;

        $asset->flagged_as_found_at = Carbon::now();
        $asset->save();

        return response()->json([
            "success" => true,
            "message" => "Whoa! we're glad you found your ".$asset->name." If Skydah helped you find it, kindly share a testimonial from your dashboard!",
            "data" => $asset->toArray()
        ], 200);

    }
/*
    public function getDocumentHash(UploadedFile $file){

        if($file) {
            $docName = $file->getClientOriginalName();
            $tempPath = $file->getRealPath();
            $file->move('uploads', $docName);    //copied to uploads folder
            $docPath = public_path('uploads'."/".$docName);    //store in db
            $docHash = hash_file( 'sha256', $docPath ); //hash the file content

            //$docHash = hash( 'sha256', $docPath ); //use this to hash the file name
            //$doc = fopen($docPath1,"r");  use this to read the file
            //$docName = time().'_'.$request->file('file')->getClientOriginalName(); //Create unique file names using timestamps

            return ['hash' => $docHash,
                    'path' => $docPath
            ];
        }
   }
*/
    public function getLocation($lat, $lng)
    {
        $client = new \GuzzleHttp\Client();
        $geocoder = new Geocoder($client);
        $geocoder->setApiKey(config('geocoder.key'));
        //$geocoder->setCountry(config('geocoder.country', 'NG'));  //restrict to a specific country
        //$address = $geocoder->getCoordinatesForAddress('12, Bayo Street, Egbeda Lagos');
        $location = $geocoder->getAddressForCoordinates($lat, $lng);

        return $location['formatted_address'];
    }

    public function getOTP($identifier)
    {
        $otp =  Otp::setValidity(15)  // otp validity time in mins
                    ->setLength(6)  // Lenght of the generated otp
                    ->setMaximumOtpsAllowed(6) // Number of times allowed to regenerate otps
                    ->setOnlyDigits(true)  // generated otp contains mixed characters ex:ad2312
                    ->setUseSameToken(false) // if you re-generate OTP, you will get same token
                    ->generate($identifier);
        
        return $otp;
    }

    public function sendOTP(Request $request)
    {
        $identifier = $request->identifier;
        $otp = $this->getOTP($identifier);
        $title = 'Skydah OTP';
        $alert = 'Your OTP is '.$otp->token. '. It will expire in 15 minutes';
        $this->sendSMS(auth()->user()->phone, $alert);    //$this->sendSMS($identifier, $alert);
        $this->sendEmail(auth()->user()->email, $title, $alert);

        if ( ! ($otp->status) )
        return response()->json([
            'success' => false,
            'message' => $otp->message,
        ], 412);

        return response()->json([
            'success' => true,
            'OTP' => $otp->token,
            'message' => 'An OTP has been sent your registered phone number. Please, use it to authorize this operation! It will expire in 15 minutes.'
        ], 200);
        
    }
 
    public function verifyOTP(Request $request)
    {
        $identifier = $request->identifier;
        $token = $request->token;
        $verified = Otp::setAllowedAttempts(2) // number of times they can allow to attempt with wrong token
                    ->validate($identifier, $token);
    
        if ( !($verified->status) )
        return response()->json([
            'success' => false,
            'message' => $verified->message,
        ], 412);

        return response()->json([
            'success' => true,
            'status' => 'OTP Verified!',
            'message' => $verified->message,
        ],200);
    }

    public function search(Request $request)
    {
        $params = $request->except('_token');
        $assets = Asset::filter($params)->get();

        return response()->json([
            'assets' => $assets
        ]);
    }

    public function company_assets(Request $request)
    {   //JUST TESTING own scope's FUNCTIONALITY
    //    dd(auth()->user()->company);
        $params = $request->except('_token');
        $assets = Asset::own($params)->get();

        return response()->json([
            'assets' => $assets,
            'asset_count' => $assets->count()
        ]);
    }

    public function getDataScope() {
        //Identify and distinguish between an individual user and a company rep & retrieve corresponding assets
        $value = is_null( auth()->user()->company_id ) ? auth()->user()->id : auth()->user()->company_id;
        $key = is_null(auth()->user()->company_id) ? 'user_id' : 'company_id';

        return [
            'key' => $key,
            'value' => $value
        ];
    }

    public function getGraphData($type = null) {
        //we can modify this to return a specific asset type count using the $type param but now, it's not in use
        $assetTypes = DB::table('assets')
        ->leftJoin('types', 'assets.type_id', '=', 'types.id')
        ->selectRaw('type, count(type_id) as count')->groupBy('type_id')
        ->where('user_id', auth()->user()->id)
        ->get();

        //Get asset counts
        $count = [];
        $count['all_assets'] = auth()->user()->assets->count();
        $count['missing_assets'] = auth()->user()->assets->whereNotNull('flagged_as_lost_at')->count();
        $count['recovered_assets'] = auth()->user()->assets->whereNotNull('flagged_as_found_at')->count();
        $count['transferred_assets'] = auth()->user()->transfers->count();

        return response()->json([
            'asset' => $assetTypes,
            'counts' => $count
        ]);
    }

    public function sendNotification($recipient, $data, $title, $alert)
    {
        //If user has enabled notifications
        if ( $recipient->allows_notification )
        {  
            //Save to notifications table
            $recipient->notify(new AssetRecoveryNotification($data));
        }        
        
        $this->sendSMS($recipient->phone, $alert);
        $this->sendEmail($recipient->email, $title, $alert);
        //$this->coaSendEmail($recipient->email, $title, $recipient->name); //works 
    }

}

/*
public function getDocumentHash(Request $request){

        if($request->file('file')) {
            $doc = $request->file('file');
            $docName = $doc->getClientOriginalName();
            $tempPath = $doc->getRealPath();
            $doc->move('uploads', $docName);    //copied to uploads folder
            $docPath = public_path('uploads'."/".$docName);    //store in db
            $docHash = hash_file( 'sha256', $docPath ); //hash the file content

            //$docHash = hash( 'sha256', $docPath ); //use this to hash the file name
            //$doc = fopen($docPath1,"r");  use this to read the file
            //$docName = time().'_'.$request->file('file')->getClientOriginalName(); //Create unique file names using timestamps

           if( Asset::where('hash', $docHash)->firstOrNull() != null) {
                //abort(400); // abort upload... file already exists
                return response()->json([
                    "success" => false,
                    "message" => "This asset has already been registered. If you did not register, please notify us from your dashboard."
                ], 422);
            }
        }
   }
*/