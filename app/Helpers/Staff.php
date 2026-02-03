<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;
use App\Models\User;

class Staff
{
    public static function user(): ?User
    {
        if (!Session::has('active_staff_id')) {
            return null;
        }

        return User::find(Session::get('active_staff_id'));
    }
}
