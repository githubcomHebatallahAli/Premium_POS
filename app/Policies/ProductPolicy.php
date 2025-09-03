<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;
    public function create(Admin $admin)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function edit(Admin $admin, Product $product)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function update(Admin $admin, Product $product)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function showAll(Admin $admin)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function showAllProduct(Admin $admin)
    {
        return in_array($admin->role_id, [1,2,3]);
    }

    public function showProductLessThan5(Admin $admin)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function importProducts(Admin $admin)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function exportProducts(Admin $admin)
    {
        return in_array($admin->role_id, [1,2]);
    }


}
