<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Category;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;
    public function create(Admin $admin)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function edit(Admin $admin, Category $category)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function update(Admin $admin, Category $category)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function showAll(Admin $admin)
    {
        return in_array($admin->role_id, [1,2]);
    }

    public function showAllCat(Admin $admin)
    {
        return in_array($admin->role_id, [1,2,3]);
    }

}
