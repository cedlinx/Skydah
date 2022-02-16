<?php

namespace App\Imports;

use App\Models\Transfer;    //Asset;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use App\Models\User;
use xtype\Eos\Client;   //blockchain

//This was designed to be used for bulk asset registration but is now being used for asset transfer... Take note!

class AssetsImport implements ToModel, WithStartRow, WithCustomCsvSettings
{
    public function startRow(): int
    {
        return 2;
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';'
        ];
    }
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        //Get ID of recipient. This is necessary because the CSV contains recipient's email which is easier to get than the required ID
        $userFound = User::where('email', $row[0])->first();
        if ( ! $userFound ) {
            $newOwner = 0; 
        } else {
            $newOwner = $userFound->id;
        }    //abort(500, 'User not found!'); 
            /*return response()->json([    //NOTE: If this is NOT the first record, then some would already have been successfully transferred... Handle that
                'success' => false,
                'message' => 'Recipient with email: '.$row[0].' does not exit',
            ], 404);
            */

        //CONFIRM THAT ASSET BELONGS TO THE LOGGED IN USER
        //$aID = Asset::where('skydahid', $row[2])->first();
        //$asset = auth()->user()->assets->find($aID->id); //($request->id);

        $assetFound = auth()->user()->assets->where('skydahid', $row[2])->first();   //Can use this one-liner instead of the 2 above. Then we'll use $asset->id in place of $aID->id below
        if ( ! $assetFound ) {
            $asset_id = 0;
         } else {
            //Change asset's user_id to the new owner's
            $assetFound->user_id = $newOwner;    //$request->transferTo;
            $assetFound->save();
            $asset_id = $assetFound->id;
        }   //abort(500, 'Asset not found or Unauthorized.');
            /*return response()->json([
                'success' => false,
                'message' => 'Asset with SkydahId: '.$row[2].' either does not belong to you or does not exist. Please check your entry and try again.'
            ], 422);
            */


        //$message = 'Asset has been fully transferred! Forwarding to the blockchain...';
        
        //effect transfer on the blockchain        
   //     $txnID = $this->transferAsset($asset->id, $newOwner->id);

        //add transaction ID to the transferred record
        //$transfer->transactionId = $txnID;

        //Save transfer details to database
        return new Transfer([
            'user_id' => auth()->user()->id,
            'newOwner'     => $newOwner, //$row[0],
            'transferReason'    => $row[1],
            'asset_id' => $asset_id,   //$row[2],
        //    'transactionId' => $txnID
        ]);
    }

    private function transferAsset(int $id, int $newOwnerID){   
        //Set the url - testnet or mainnet
        $client = new Client(env('TESTNET_URL'));
    
        // set your active private key
        $client->addPrivateKeys([
            env('TESTNET_ACTIVE_ACCOUNT_PRIVKEY')
        ]);
    
        $tx = $client->transaction([
            'actions' => [
                [
                    'account' => env('TESTNET_ACCOUNT_NAME'), //set the testnet/mainet account name
                    'name' => 'transasset',
                    'authorization' => [[
                        'actor' => env('TESTNET_ACCOUNT_NAME'),
                        'permission' => 'active',
                    ]],
                    'data' => [
                      'asset_id' => $id,    //1, //primary key from pgsql/mysql database
                      'asset_skydah_owner' => $newOwnerID,    //'Obinnah', //Asset new owner name or identity
                  ],
                ]
            ]
        ]);
        //echo "Transaction ID: {$tx->transaction_id}"; //Store in db
        return $tx->transaction_id;
      }
} 