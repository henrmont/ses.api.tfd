<?php

namespace App\Http\Controllers;

use App\Services\RoleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function index(): JsonResponse
    {
        $this->authorize('tfd/regra listar');

        $roles = $this->roleService->getAllRoles();

        return response()->json($roles, JsonResponse::HTTP_OK);
    }

    /**
     * Listar todas as permissões do módulo TFD.
     */
    public function permissions(): JsonResponse
    {
        $this->authorize('tfd/regra listar');

        $permissions = $this->roleService->getAllPermissions();

        return response()->json($permissions, JsonResponse::HTTP_OK);
    }

    /**
     * Criar uma nova regra.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('tfd/regra criar');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer'],
        ]);

        $this->roleService->createRole($validated);

        return response()->json(
            ['message' => 'Regra criada com sucesso.'],
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * Atualizar dados e permissões de uma regra.
     */
    public function update(Role $role, Request $request): JsonResponse
    {
        $this->authorize('tfd/regra atualizar');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer'],
        ]);

        $this->roleService->updateRole($role, $validated);

        return response()->json(
            ['message' => 'Regra atualizada com sucesso.'],
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Excluir uma regra.
     */
    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('tfd/regra deletar');

        $this->roleService->deleteRole($role);

        return response()->json(
            ['message' => 'Regra deletada com sucesso.'],
            JsonResponse::HTTP_OK
        );
    }
}