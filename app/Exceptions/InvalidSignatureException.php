<?php

namespace App\Exceptions;

use Exception;

class InvalidSignatureException extends Exception
{
   
    // ...
    /**
     * Get the exception's context information.
     *
     * @return array
     */
    public function context()
    {
        return ['user_email' => $this->email];
    }

        /**
     * Report the exception.
     *
     * @return bool|null
     */
    public function report()
    {
        //
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response()->json(['Error' => 'Invlid Signature! Please request another activation email.']);
    }
}