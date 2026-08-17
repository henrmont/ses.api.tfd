<?php

namespace App\Services;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    /**
     * Criar uma nova regra no sistema com suas permissões.
     */
    public function createRole(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $role = Role::on('auth')->create([
                'name' => 'tfd/' . $request->name,
            ]);

            $selectedPermissions = Permission::findMany($request->permissions);
            $defaultPermissions = $this->getDefaultPermissions();

            $role->givePermissionTo($selectedPermissions->merge($defaultPermissions));

            DB::commit();

            return response()->json(['message' => 'Regra criada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar regra: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar dados e permissões de uma regra existente.
     */
    public function updateRole(Role $role, Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $role->update([
                'name' => 'tfd/' . $request->name,
            ]);

            $selectedPermissions = Permission::findMany($request->permissions);
            $defaultPermissions = $this->getDefaultPermissions();

            $role->syncPermissions($selectedPermissions->merge($defaultPermissions));

            DB::commit();

            return response()->json(['message' => 'Regra atualizada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar regra: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Excluir uma regra do sistema.
     */
    public function deleteRole(Role $role): JsonResponse
    {
        try {
            $role->delete();

            return response()->json(['message' => 'Regra deletada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao excluir regra: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Retorna as permissões padrão obrigatórias do módulo TFD.
     */
    private function getDefaultPermissions(): Collection
    {
        return Permission::whereIn('name', ['tfd/voltar', 'tfd/download'])->get();
    }
}