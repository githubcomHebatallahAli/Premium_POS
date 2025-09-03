<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminPolicy
{

    use HandlesAuthorization;
    public function create(Admin $admin)
    {
        // return $admin->role_id === 1;
        return $admin->role->name === 'Super Admin';
    }
    public function notActive(Admin $admin)
    {
        // return $admin->role_id === 1;
        return $admin->role->name === 'Super Admin';
    }
    public function active(Admin $admin)
    {
        // return $admin->role_id === 1;
        return $admin->role->name === 'Super Admin';
    }

    public function logout(Admin $admin)
    {
        // جميع الإداريين يمكنهم تسجيل الخروج
        return true;
}
}
