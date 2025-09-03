<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Supplier;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPolicy
{
    use HandlesAuthorization;
    public function create(Admin $admin)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function edit(Admin $admin, Supplier $supplier)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function update(Admin $admin, Supplier $supplier)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function showAll(Admin $admin)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function active(Admin $admin ,Supplier $supplier)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function notActive(Admin $admin, Supplier $supplier)
    {
        return in_array($admin->role_id, [1,2]);
    }


}
