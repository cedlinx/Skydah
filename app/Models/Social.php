<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Social extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider', 'imageurl', 'profile_url'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}

