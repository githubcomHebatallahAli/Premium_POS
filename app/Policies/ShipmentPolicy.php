<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Shipment;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShipmentPolicy
{
    use HandlesAuthorization;
    public function create(Admin $admin)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function edit(Admin $admin, Shipment $shipment)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function update(Admin $admin, Shipment $shipment)
    {
        return in_array($admin->role_id, [1,2]);
    }
    
    public function updatePaidAmount(Admin $admin, Shipment $shipment)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function showAll(Admin $admin)
    {
        return in_array($admin->role_id, [1,2]);
    }


    }

