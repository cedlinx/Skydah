<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Config extends Model          //This model was initially named SETTING and was for settings table until I decided to use a Settings Package
{
    use HasFactory;

    protected $fillable = [
        'setting', 'value', 'group'
    ];
}
