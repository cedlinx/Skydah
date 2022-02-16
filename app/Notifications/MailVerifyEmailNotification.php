<?php
                                    //NOT IN USE ??
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;
use Illuminate\Auth\Notifications\VerifyEmail;


class MailVerifyEmailNotification extends Notification
{
    use Queueable;
    protected $pageUrl;
    public $token;
    public static $toMailCallback;
    public $email;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->pageUrl = 'http://localhost:8080';
    //    $this->token = $token;  //this comes from the call in User Model and gets constructed here
    //    $this->email = $email;  //this comes from the call in User Model and gets constructed here
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
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }
        {
        return (new MailMessage)
        ->subject(Lang::get('Skydah Account Activation'))
        ->line(Lang::get('You are receiving this email because you registered on Skydah.'))
//        ->action(Lang::get('Verify Email'), url("api/email-verified-with?token=".$this->token))
//        ->action(Lang::get('Verify Email'), url("api/email/verify/{id}/{hash}"))    //Last used and working perfectly until Grace requested a change, then the next line was introduced and this removed
        ->action(Lang::get('Verify Email'), url("api/verify_email/{token}"))
        ->line(Lang::get('This account activation link will expire in :count minutes.', ['count' => config('auth.passwords.users.expire')]))
        ->line(Lang::get('If you did not register on Skydah, simply ignore this email.'));
                    
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
