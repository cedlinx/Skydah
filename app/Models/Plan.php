<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name', 'amount', 'description', 'account_type', 'no_of_devices'
    ];
}
