<?php

namespace App\Policies;

use App\Models\Dept;
use App\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeptPolicy
{
    use HandlesAuthorization;
    public function create(Admin $admin)
    {
        return in_array($admin->role_id, [1,2,3]);
    }

    public function edit(Admin $admin, Dept $dept)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function update(Admin $admin, Dept $dept)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function updatePaidAmount(Admin $admin, Dept $dept)
    {
        return in_array($admin->role_id, [1,2]);
    }


    public function showAll(Admin $admin)
    {
        return in_array($admin->role_id, [1,2]);
    }

}
