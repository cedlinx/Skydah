<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Mail;
use App\Mail\MyTestMail;

class EmailServiceController extends Controller
{
    public function send_email(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'subject' => 'required',
            'message' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $email = $request->email;
        $subject = $request->subject;
        $message = $request->message;

        $details = [
            'title' => $subject,
            'body' => $message
        ];

        try {
            Mail::to($email)->send(new MyTestMail($details));
            return $this->sendSuccess('Email is Sent, please check your inbox.', $data = $email);  
        } catch (Exception $e) {
            return $this->sendError('Error sending email: '.$e->getMessage(), $data = []);
        }
    }

}
