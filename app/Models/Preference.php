<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Preference extends Model
{
    use HasFactory;

    protected $fillable = [
        'allows_notification'
    ];

    public function user()
    {
        return $this->hasOne(User::class)->withDefault([
            'name' => 'Unknown User',
        ]);
    }
}
