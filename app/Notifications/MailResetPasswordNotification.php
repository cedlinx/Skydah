<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Lang;
use Illuminate\Notifications\Notification;
//use Illuminate\Support\Facades\DB;


//class MailResetPasswordNotification extends ResetPassword
class MailResetPasswordNotification extends Notification
{
    use Queueable;
    protected $pageUrl;
    public $token;
    public static $toMailCallback;
    public $email;
    /**
    * Create a new notification instance.
    * @return void
    * @param $token
    */
    public function __construct($token, $email)
    {
    //    parent::__construct($token); //this line was causing an error so I replaced it with the next THREE
        $this->pageUrl = 'http://localhost:8080';
        $this->token = $token;  //this comes from the call in User Model and gets constructed here
        $this->email = $email;  //this comes from the call in User Model and gets constructed here
            // we can set whatever we want here, or use .env to set environmental variables
    }
    /**
    * Get the notification's delivery channels.
    *
    * @param  mixed  $notifiable
    * @return array
    */
    public function via($notifiable)
    {   
        return ['mail'];
    }
    /**
    * Get the mail representation of the notification.
    *
    * @param  mixed  $notifiable
    * @return \Illuminate\Notifications\Messages\MailMessage
    */
    public function toMail($notifiable)
    {
        $email = $this->email;      
        $customUrl = "https://app.skydah.com/reset_password/".$this->token;          
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

//This works perfectly but I wanted to try out the Lang facade which provides for dynamic/variable :count
    /*    return (new MailMessage)
            ->subject('Request for Password Reset')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', url($this->pageUrl."?token=".$this->token))
            ->line('This password reset link will expire in 60 minutes.', ['count' => config('auth.passwords.users.expire')])
            ->line('If you did not request a password reset, no further action is required.');
    */
        return (new MailMessage)
            ->subject(Lang::get('Reset Password Request'))
            ->line(Lang::get('You are receiving this email because we received a password reset request for your account.'))
            ->action(Lang::get('Reset Password'), $customUrl)   //url("reset_password/".$this->token))
            ->line(Lang::get('This password reset link will expire in :count minutes.', ['count' => config('auth.passwords.users.expire')]))
            ->line(Lang::get('If you did not request a password reset, no further action is required.'));
        
    } 
     
    /**
    * Get the array representation of the notification.
    *
    * @param  mixed  $notifiable
    * @return array
    */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

