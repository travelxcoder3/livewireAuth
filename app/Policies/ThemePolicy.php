<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Agency;
use Illuminate\Auth\Access\Response;


class ThemePolicy
{
    /**
     * السماح فقط للسوبر أدمن أو أدمن الوكالة بتعديل الثيم
     */
    public function update(User $user, Agency $agency)
    {
        return $user->hasRole('super-admin') ||
               ($user->hasRole('agency-admin') && $user->agency_id === $agency->id ) ? Response::allow()
               : Response::deny('ليس لديك صلاحية لتغيير لون الثيم');
    }
} 