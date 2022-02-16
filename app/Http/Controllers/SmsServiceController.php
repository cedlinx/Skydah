<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;

class SmsServiceController extends Controller
{
    public function send_user_sms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipients' => 'required',
            'message' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        } else {
            $message = $request->message;
            $recipients = $request->recipients;
            $this->sendSMS($recipients, $message);
        }
    }

}
