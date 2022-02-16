<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recovery extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id', 'user_id', 'location', 'lat', 'lng', 'owner'
    ];

    public function user()
    {
        //This user is the Secondary owner, ie, the one who's trying to register an already registered Asset.
        //The primary owner, ie, the original owner, is available through the asset->user using the asset_id FK
        return $this->belongsTo(User::class)->withDefault([
            'name' => 'Unknown User',
        ]);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class)->withDefault([
            'name' => 'Unknown User',
        ]);
    }
}
