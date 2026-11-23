<?php

namespace App\Services;

use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    private const MODULE_PREFIX = 'tfd/';
    private const DB_CONNECTION = 'auth';
    private const DEFAULT_PERMISSIONS = [
        'tfd/voltar',
        'tfd/download',
    ];

    /**
     * Retorna todas as regras pertencentes ao módulo TFD.
     */
    public function getAllRoles(): Collection
    {
        return Role::query()
            ->with('permissions')
            ->where('name', 'LIKE', self::MODULE_PREFIX . '%')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Retorna todas as permissões pertencentes ao módulo TFD.
     */
    public function getAllPermissions(): Collection
    {
        return Permission::query()
            ->where('name', 'LIKE', self::MODULE_PREFIX . '%')
            ->get();
    }

    /**
     * Cria uma nova regra com suas permissões selecionadas e padrão.
     */
    public function createRole(array $data): Role
    {
        return DB::connection(self::DB_CONNECTION)->transaction(function () use ($data) {
            $role = Role::on(self::DB_CONNECTION)->create([
                'name' => $this->formatRoleName($data['name']),
            ]);

            $permissions = $this->resolvePermissions($data['permissions'] ?? []);
            $role->givePermissionTo($permissions);

            return $role;
        });
    }

    /**
     * Atualiza o nome e sincroniza as permissões de uma regra existente.
     */
    public function updateRole(Role $role, array $data): Role
    {
        $this->ensureBelongsToModule($role);

        return DB::connection(self::DB_CONNECTION)->transaction(function () use ($role, $data) {
            $role->update([
                'name' => $this->formatRoleName($data['name']),
            ]);

            $permissions = $this->resolvePermissions($data['permissions'] ?? []);
            $role->syncPermissions($permissions);

            return $role;
        });
    }

    /**
     * Remove uma regra do sistema.
     */
    public function deleteRole(Role $role): void
    {
        $this->ensureBelongsToModule($role);

        DB::connection(self::DB_CONNECTION)->transaction(function () use ($role) {
            $role->delete();
        });
    }

    /**
     * Garante que o nome da regra tenha exatamente um prefixo do módulo.
     */
    private function formatRoleName(string $name): string
    {
        $cleanName = preg_replace('/^' . preg_quote(self::MODULE_PREFIX, '/') . '/i', '', trim($name));

        return self::MODULE_PREFIX . $cleanName;
    }

    /**
     * Impede alteração/exclusão de regras que não pertencem ao módulo TFD.
     */
    private function ensureBelongsToModule(Role $role): void
    {
        if (!str_starts_with($role->name, self::MODULE_PREFIX)) {
            throw new DomainException('A regra informada não pertence ao módulo TFD.');
        }
    }

    /**
     * Mescla as permissões selecionadas (restritas ao módulo) com as permissões padrão.
     */
    private function resolvePermissions(array $permissionIds): Collection
    {
        $selectedPermissions = Permission::query()
            ->where('name', 'LIKE', self::MODULE_PREFIX . '%')
            ->whereIn('id', $permissionIds)
            ->get();

        $defaultPermissions = Permission::query()
            ->whereIn('name', self::DEFAULT_PERMISSIONS)
            ->get();

        return $selectedPermissions->merge($defaultPermissions);
    }
}