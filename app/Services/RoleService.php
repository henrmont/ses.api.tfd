<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function createRole(Request $request)
    {
        try {
            DB::beginTransaction();
            $role = Role::on('auth')->create(['name' => 'tfd/'.$request->name]);
            $role->givePermissionTo(Permission::findMany($request->permissions)->merge(Permission::whereIn('name',['tfd/voltar','download'])->get()));
            DB::commit();
            return response()->json(['message' => 'Regra criada com sucesso.'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateRole(Role $role, Request $request)
    {
        try {
            DB::beginTransaction();
            $role->update(['name' => 'tfd/'.$request->name]);
            $role->syncPermissions(Permission::findMany($request->permissions)->merge(Permission::whereIn('name',['tfd/voltar','download'])->get()));
            DB::commit();
            return response()->json(['message' => 'Regra atualizada com sucesso.'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteRole(Role $role)
    {
        try {
            $role->delete();
            return response()->json(['message' => 'Regra deletada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}