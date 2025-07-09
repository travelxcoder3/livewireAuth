<?php

namespace App\Policies;

use App\Models\User;

class SystemPolicy
{
    public function manageSystem(User $user)
    {
        return $user->hasRole('super-admin');
    }
}