<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Invoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;
    public function create(Admin $admin)
    {
        return in_array($admin->role_id, [1,2,3]);
    }

    public function edit(Admin $admin, Invoice $invoice)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function update(Admin $admin, Invoice $invoice)
    {
        return in_array($admin->role_id, [1,2]);
    }


    public function showAll(Admin $admin)
    {
        return in_array($admin->role_id, [1,2]);
    }

}
