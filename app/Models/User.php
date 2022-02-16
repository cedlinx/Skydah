<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

//COA: added to handle forgot/reset password
use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Passwords\CanResetPassword as canReset;   //NOTE the difference between this and the next line. the next is an interface, this is a Trait. //I added "as canReset" to avoid a conflict
use Illuminate\Contracts\Auth\CanResetPassword;     //Interface
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Preference;
use Smartisan\Settings\HasSettings;

//COA: Email verification
use Illuminate\Contracts\Auth\MustVerifyEmail as mustVerify;    //interface


class User extends Authenticatable implements CanResetPassword, mustVerify
{
    use HasFactory, HasApiTokens, Notifiable, SoftDeletes, HasSettings;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar', 
        'provider_id', 
        'provider',
        'access_token',
        'address',
        'phone',
        'email_verified_at',
        'email_verified',
        'group_id',
        'pin',
        'pinsos',
        'company_id',
        'alternate_phone',
        'secondary_email',
        'asset_limit',
        'verification_url',
        'verification_token',
        'plan_id',
        'asset_limit'
    ];
    
    protected $guarded = ['*'];
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'deleted_at',
        'remember_token', 'pin', 'pinSOS', 'email_verified', 'email_verification_token', 'access_token', 'api_token',
        'verification_token', 'verification_url'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sendPasswordResetNotification($token)
    { 
        //very important that these 2 variables get passed to the __construct function IN MailResetPasswordNotification (in the same order)
        $this->notify(new \App\Notifications\MailResetPasswordNotification($token, $this->email));
    }

    public function sendEmailVerificationNotification()
    {
      //  $email = 'cedlinx@yahoo.com';
      //  $this->notify(new \App\Notifications\MailVerifyEmailNotification($email));
      $this->notify(new \Illuminate\Auth\Notifications\VerifyEmail());    //COA added custom code in AuthServiceProvider's boot to customize verification url, etc
      //$this->notify(new \App\Notifications\CustomVerifyEmail());  //Working perfectly too... without this, CustomVerifyEmail (notification) will not be required
    }
 
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function userRole()
    {
        return $this->belongsTo(Role::class)->withDefault([
            'role' => 'Guest',
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

    public function group() {
        return $this->belongsTo(Group::class)->withDefault([
            'group' => 'Unknown'
        ]);
    }

    public function company() {
        return $this->belongsTo(Company::class)->withDefault([
            'company' => 'Not Applicable'
        ]);
    }

    public function socials()
    {
        return $this->belongsToMany(Social::class);
    }

    public function isDelegate() {
        
    }

    public function isAdmin() {
        if ($this->group_name == 'Admin' || $this->group_name == 'Super Admin') return true;
        return false;
    }

    public function preference()
    {
        return $this->belongsTo(Preference::class); //I'd want this to be hasOne(Preference::class)
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class)->withDefault([
            'id' => 0,
            'name' => 'Not subscribed',
            'account_type' => 'N/A',
            'no_of_devices' => 0,
            'description' => 'Not applicable'
        ]);
    }
}


