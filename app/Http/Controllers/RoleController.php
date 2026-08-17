<?php

namespace App\Http\Controllers;

use App\Services\RoleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected RoleService $roleService
    ) {}

    /**
     * Listar todas as regras do módulo TFD.
     */
    public function getRoles(): JsonResponse
    {
        $this->authorize('tfd/regra listar');

        $roles = Role::query()
            ->with('permissions')
            ->where('name', 'LIKE', 'tfd%')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($roles, JsonResponse::HTTP_OK);
    }

    /**
     * Listar todas as permissões do módulo TFD.
     */
    public function getPermissions(): JsonResponse
    {
        $this->authorize('tfd/regra listar');

        $permissions = Permission::query()
            ->where('name', 'LIKE', 'tfd%')
            ->get();

        return response()->json($permissions, JsonResponse::HTTP_OK);
    }

    /**
     * Criar uma nova regra.
     */
    public function createRole(Request $request): JsonResponse
    {
        $this->authorize('tfd/regra criar');

        return $this->roleService->createRole($request);
    }

    /**
     * Atualizar dados de uma regra.
     */
    public function updateRole(Role $role, Request $request): JsonResponse
    {
        $this->authorize('tfd/regra atualizar');

        return $this->roleService->updateRole($role, $request);
    }

    /**
     * Excluir uma regra.
     */
    public function deleteRole(Role $role): JsonResponse
    {
        $this->authorize('tfd/regra deletar');

        return $this->roleService->deleteRole($role);
    }
}