<?php

use App\Http\Controllers\Admin\RolePermissionsController;
use Illuminate\Support\Facades\Route;



Route::controller(RolePermissionsController::class)->prefix('/admin')->middleware('admin')->group(
    function () {
    Route::get('/showAll/roles/with/permissions','showAllRolesWithPermissions');
   Route::post('/assign/role/to/permissions','assignRoleToPermissions');
   Route::delete('/revoke/role/from/permissions', 'revokeRoleFromPermissions');
   
   });
