<?php

use App\Http\Controllers\Admin\RolePermissionsController;
use Illuminate\Support\Facades\Route;



Route::controller(RolePermissionsController::class)->prefix('/admin')->middleware('admin')->group(
    function () {
    Route::get('/showAll/roles/with/permissions','showAllRolesWithPermissions');
    Route::get('/show/role/{id}/with/permissions','showRoleWithPermissions');
   Route::post('/assign/role/{role}/to/permissions','assignRoleToPermissions');
   Route::delete('/revoke/role/{role}/from/permissions', 'revokeRoleFromPermissions');
   
   });
