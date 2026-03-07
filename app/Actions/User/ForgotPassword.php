<?php

namespace App\Actions\User;

class ForgotPassword
{
    public function execute()
    {
        return response()->json([
            'message' => "Hello everyone"
        ]);
    }
}
