<?php

namespace App\Http\Controllers;

use App\Services\RoleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use AuthorizesRequests;

    public function getRoles()
    {
        $this->authorize('tfd/regra listar');
        $roles = Role::query()
            ->with('permissions')
            ->where('name','LIKE','tfd%')
            ->orderBy('id','desc')
            ->get();
        return response()->json($roles, 200);
    }

    public function getPermissions()
    {
        $this->authorize('tfd/regra listar');
        $permissions = Permission::query()
            ->where('name','LIKE','tfd%')
            ->get();
        return response()->json($permissions, 200);
    }

    public function createRole(Request $request, RoleService $roleService)
    {
        $this->authorize('tfd/regra criar');
        return $roleService->createRole($request);
    }

    public function updateRole(Role $role, Request $request, RoleService $roleService)
    {
        $this->authorize('tfd/regra atualizar');
        return $roleService->updateRole($role, $request);
    }

    public function deleteRole(Role $role, RoleService $roleService)
    {
        $this->authorize('tfd/regra deletar');
        return $roleService->deleteRole($role);
    }
}
