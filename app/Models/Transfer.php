<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $fillable = [
        'transferReason', 'asset_id', 'newOwner', 'user_id', 'transactionId'
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class)->withDefault([
            'asset' => 'Unknown',
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => 'Unknown User',
        ]);
    }

}
