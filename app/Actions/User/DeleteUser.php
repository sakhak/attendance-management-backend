<?php

namespace App\Actions\User;

use App\Models\User;

class DeleteUser
{
    public function execute(int $id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return true;
    }
}
