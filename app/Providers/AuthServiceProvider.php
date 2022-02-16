<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

// For custom verifyEmail added here in boot
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        
        if (! $this->app->routesAreCached()) {
            Passport::routes();
        }
        
        VerifyEmail::toMailUsing(function ($notifiable) {

            $signedUrl = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            $customToken = hash( 'sha256', $signedUrl ); //hash the original Laravel generated verification url and use it as token for SKydah FrontG
            $customUrl = "https://app.skydah.com/verify_email"."/".$customToken;

            $notifiable->verification_token = $customToken;
            $notifiable->verification_url = $signedUrl;        
            $notifiable->save();

            return (new MailMessage)
                ->subject(Lang::get('Verify Email Address'))
                ->line(Lang::get('Please click the button below to verify your email address.'))
                ->action(Lang::get('Verify Email Address'), $customUrl)
                ->line(Lang::get('If you did not create an account, no further action is required.'));
        });
    }
}   //  END 

