<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'payment_reference', 'payment_type', 'amount_paid', 'payment_status'
    ];
}
