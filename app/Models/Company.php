<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name', 'code', 'email' //, 'group_id'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function group()
    {
       // return $this->belongsTo(Group::class); 
    }

}
