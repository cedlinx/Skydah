<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\User;
use App\Models\Type;

class Asset extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'name', 'description', 'skydahid', 'assetid', 'type_id', 'user_id', 'transferable', 'file',
        'hash', 'location', 'lat', 'lng', 'sos', 'receipt', 'company_id'
    ];

    protected $casts = [
        'sos' => 'boolean',
        'verified' => 'boolean',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => 'Unknown User',
        ]);
    }

    public function type()
    {
        return $this->belongsTo(Type::class)->withDefault([
            'type' => 'Unknown',
        ]);
    }

    public function recoveries()
    {
        return $this->hasMany(Recovery::class);
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class)->withDefault([
            'company' => 'Unknown'
        ]);
    }

    //Use this scope rule to filter the Asset model, eg. in ad-hoc searches. Result are based on ANDs but we can use other values like orWhere, notNull, etc 
    public function scopeFilter($query, $params)
    {
        if ( isset($params['name']) && trim($params['name'] !== '') ) {
            $query->where('name', 'LIKE', trim($params['name']) . '%');
        }

        if ( isset($params['assetid']) && trim($params['assetid']) !== '' )
        {           //AND   //exact match
            $query->where('assetid', '=', trim($params['assetid']));
        }

        if ( isset($params['skydahid']) && trim($params['skydahid']) !== '' )
        {           //AND   //exact match
            $query->where('skydahid', '=', trim($params['skydahid']));
        }
        return $query;

        //Eg.: //    $asset = Asset::filter($params);
    }

    public function scopeOwn($query, $params)
    {
        if ( ! is_null(auth()->user()->company_id ) ) {
            $query->where('company_id', '=', auth()->user()->company_id);
        //    ->where('skydahid', '=', trim($params['skydahid']));  //use for further filtering
        } else {
            $query->where('user_id', '=', auth()->user()->id);
        }

        return $query;

        //Example usage in a controller:
        //     $params = $request->except('_token');
        //     $asset = Asset::own($params)->where('skydahid', $params['skydahid'])->get(); //remove the where clause to return all records

    }

    //Eloquent for: Select Case ... When:   Accessors (Mutators)
    public function getAssetStatusAttribute()
    {
        if ( is_null($this->flagged_as_lost_at) ) return 'Okay';

        return 'Lost or Stolen';
    }

    public function getAssetStatusMessageAttribute($value)
    {
        if ( is_null($this->flagged_as_lost_at) ) return 'Asset is safe & intact';

        return 'This asset was reported Lost or Stolen';
    }

    /*
    public function getAssetCategoryAttribute($value)
    {
        //Works only for SINGLE ASSET views (verify asset) but not in use
        $types = Type::all();
        foreach ($types as $type){
            if ( $this->type_id == $type->id ) {
                $category = $type->type;
            }
        }
        if ( ! $category ) return 'Unknown';

        return $category;
    }
    */

    /*

// collect our filter fields from the request
$status = request()->query('status');
$gender = request()->query('gender');
$city = request()->query('city');
$country = request()->query('country');
$state = request()->query('state');
$name = request()->query('name');

// query build up and getting users
$users = User::when($status, function($query, $status){
      return $query->where('status',$status);
  })->when($gender, function($query, $gender){
      return $query->where('gender', $gender );
  })->when($city, function($query, $city){
      return $query->where('city', $city );
  })->when($country, function($query, $country){
      return $query->where('country', $country );
  })->when($state, function($query, $state){
      return $query->where('state', $state );
  })->when($name, function($query, $name){
      return $query->where('name','like',"%$name%");
  })->get();
dd($users);
    */

}
