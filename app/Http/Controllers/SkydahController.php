<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use xtype\Eos\Client;

class SkydahController extends Controller
{

   /**
   * All param data are hard coded. Please be advised to use the Request object to pass in this parameters instead
   * Handle Exceptions as well comming from the blockchain assertions if you may encounter
   *
  * */

    public function createAsset(array $data){  //(Request $request){
      $client = new Client(env('TESTNET_URL'));

      // set your private key
      $client->addPrivateKeys([
          env('TESTNET_ACTIVE_ACCOUNT_PRIVKEY')
      ]);

      $tx = $client->transaction([
          'actions' => [
              [
                  'account' => env('TESTNET_ACCOUNT_NAME'),
                  'name' => 'createasset',
                  'authorization' => [[
                      'actor' => env('TESTNET_ACCOUNT_NAME'),
                      'permission' => 'active',
                  ]],
                  'data' => [
                      'asset_id' => 2, //primary key from pgsql/mysql database
                      'asset_skydah_id' => 'sky-cungnuire8u8fcv8dhvd', //Asset skydah ID (12-16 char alphanumeric string)
                      'asset_type' => 'Laptop-Macbook', //Asset type , can be assigned from a list of constants plus asset model
                      'asset_type_id' => 'Serial', //Type of ID associated with asset - IMEI, serial,...
                      'asset_hash' => 'uhbfufbulbblublvferuvbr', //message digest for this asset. passing a message digest is also recommended
                      'asset_skydah_owner' => 'Meshach Ishaya',// Current owner of asset
                      'asset_transferable' => true //Boolean value to set if asset can be transferred or not when created.
                  ],
              ]
          ]
      ]);
      echo "Transaction ID: {$tx->transaction_id}"; //recommended - strore in db as well
    } 
/*
    public function setValidity(Request $request){
      $client = new Client(env('TESTNET_URL'));

      // set your private key
      $client->addPrivateKeys([
          env('TESTNET_ACTIVE_ACCOUNT_PRIVKEY')
      ]);

      $tx = $client->transaction([
          'actions' => [
              [
                  'account' => env('TESTNET_ACCOUNT_NAME'),
                  'name' => 'setvalidity',
                  'authorization' => [[
                      'actor' => env('TESTNET_ACCOUNT_NAME'),
                      'permission' => 'active',
                  ]],
                  'data' => [
                      'asset_id' => 1, //primary key from pgsql/mysql database
                      'asset_validity' => true, //Asset validity refers to the state of this asset. Either a true asset or false asset and carries a boolean value
                  ],
              ]
          ]
      ]);
      echo "Transaction ID: {$tx->transaction_id}"; //recommended - strore in db as well
    }
*/
/*
    public function transferAsset(Request $request){
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
                    'asset_id' => 1, //primary key from pgsql/mysql database
                    'asset_skydah_owner' => 'Obinnah', //Asset new owner name or identity
                ],
              ]
          ]
      ]);
      echo "Transaction ID: {$tx->transaction_id}"; //Store in db
    }
*/
/*
    public function getAsset(Request $request){
      $response = Http::post(env('TESTNET_URL').'/v1/chain/get_table_rows', [
         "json" => true,
         "code" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
         "scope" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
         "table" => "skydahassets", //Skydah struct/table on the blockchain holding data
         "table_key"=> "", //primary key uint64_t - set ass db id when calling createasset action on the blockchain
         "lower_bound"=> "", // representation of lower bound value of key
         "upper_bound"=> "", // representation of upper bound value of key
         "limit"=> 10, //max number of rows to return per query
         "key_type"=> "", //key type of --index_position
         "index_position"=> "", //table can have multiple indexes apart from a primary index (uint64_t). skydah has a checksum256 secondary 1 index for query
         "encode_type"=> "bytes", //The encoding type of key_type (i64 , i128 , float64, float128) only support decimal encoding
         "reverse"=> false,//Iterate in reverse order
         "show_payer"=> false //always will be the skydah smart contract paying for RAM
      ]);

      return $response->json();
    }

    public function getAssetByHash(Request $request){
      $response = Http::post(env('TESTNET_URL').'/v1/chain/get_table_rows', [
         "json" => true,
         "code" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
         "scope" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
         "table" => "skydahassets", //Skydah struct/table on the blockchain holding data
         "key"=> "899c25bdef73717984fa9f71c4efb61bf81cd7fa198ab2e76974f6e35d2ac34d", //primary key uint64_t - set as db id when calling createasset action on the blockchain
         "lower_bound"=> "", // representation of lower bound value of key
         "upper_bound"=> "", // representation of upper bound value of key
         "limit"=> 1, //max number of rows to return per query
         "key-type"=> "sha256", //key type of --index_position. uint64_t or checksum256 for skydak
         "index"=> "2", //table can have multiple indexes apart from a primary index (uint64_t). skydah has a checksum256 secondary 1 index for query
         "encode-type"=> "bytes", //The encoding type of key_type (i64 , i128 , float64, float128) only support decimal encoding
         "reverse"=> false,//Iterate in reverse order
         "show-payer"=> false //always will be the skydah smart contract paying for RAM
      ]);

      return $response->json();
    }
*/
}


//the following contain bug fixes for asset retrieval which (above) do not return A SINGLE ASSET
/*
public function getAsset(Request $request){
    $response = Http::post(env('TESTNET_URL').'/v1/chain/get_table_rows', [
       "json" => true,
       "code" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
       "scope" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
       "table" => "skydahassets", //Skydah struct/table on the blockchain holding data
       "key"=> "2", //primary key uint64_t - set ass db id when calling createasset action on the blockchain
       "lower_bound"=> "2", // representation of lower bound value of key
       "upper_bound"=> "2", // representation of upper bound value of key
       "limit"=> 10, //max number of rows to return per query
       "key_type"=> "uint64", //key type of --index_position
       "index_position"=> "1", //table can have multiple indexes apart from a primary index (uint64_t). skydah has a checksum256 secondary 1 index for query
       "encode_type"=> "bytes", //The encoding type of key_type (i64 , i128 , float64, float128) only support decimal encoding
       "reverse"=> false,//Iterate in reverse order
       "show_payer"=> false //always will be the skydah smart contract paying for RAM
    ]);

    return $response->json();
  }

  public function getAssetByHash(Request $request){
    $response = Http::post(env('TESTNET_URL').'/v1/chain/get_table_rows', [
       "json" => true,
       "code" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
       "scope" => env('TESTNET_ACCOUNT_NAME'), //provided account names from blockchain
       "table" => "skydahassets", //Skydah struct/table on the blockchain holding data
       "key"=> "017101b02a3c3f11f410cc7c4525d4fbbe27ac88257c76d242ef4b1969c250bf", //primary key uint64_t - set as db id when calling createasset action on the blockchain
       "lower_bound"=> "017101b02a3c3f11f410cc7c4525d4fbbe27ac88257c76d242ef4b1969c250bf", // representation of lower bound value of key
       "upper_bound"=> "017101b02a3c3f11f410cc7c4525d4fbbe27ac88257c76d242ef4b1969c250bf", // representation of upper bound value of key
       "limit"=> 3, //max number of rows to return per query
       "key_type"=> "sha256", //key type of --index_position. uint64_t or checksum256 for skydak
       "index_position"=> "2", //table can have multiple indexes apart from a primary index (uint64_t). skydah has a checksum256 secondary 1 index for query
       "encode-type"=> "bytes", //The encoding type of key_type (i64 , i128 , float64, float128) only support decimal encoding
       "reverse"=> false,//Iterate in reverse order
       "show-payer"=> false //always will be the skydah smart contract paying for RAM
    ]);

    return $response->json();
  }

*/