<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Professional;
use App\Models\User;
use App\Models\UserModule;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UserService
{
    /**
     * Conexão com o banco padrão (TFD).
     */
    private function tfd(): Connection
    {
        return DB::connection();
    }

    /**
     * Conexão com o banco Core.
     */
    private function auth(): Connection
    {
        return DB::connection('auth');
    }
    
    protected ?Module $module;

    public function __construct()
    {
        $this->module = Module::where('name', 'tfd')->first();
    }

    /**
     * Criar ou associar usuário ao módulo TFD com seus dados profissionais.
     */
    public function createUser(Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();
            $this->auth()->beginTransaction();

            $user = User::where('email', $request->email)->first();
            $professionalPayload = $this->getProfessionalPayload($request);

            if (!$user) {
                // 1. Insere o usuário na tabela 'users' da conexão 'auth' e obtém o ID
                $userId = $this->auth()
                    ->table('users')
                    ->insertGetId([
                        'email'      => $request->email,
                        'name'       => $request->email,
                        'password'   => Hash::make('12345678'),
                        'module_id'  => $this->module?->id,
                    ]);
            } else {
                $user->update([
                    'is_valid' => true,
                    'module_id' => $this->module?->id,
                ]);
            }

            $this->auth()
                ->table('user_modules')
                ->insert([
                    'user_id'    => $user ? $user->id : $userId,
                    'module_id'  => $this->module?->id,
                ]);

            Professional::create(array_merge(
                ['user_id' => $user ? $user->id : $userId],
                $professionalPayload
            ));

            $this->tfd()->commit();
            $this->auth()->commit();

            return response()->json(['message' => 'Usuário criado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            $this->auth()->rollBack();

            Log::error('Erro ao criar usuário: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Alternar trava de edição (is_editable) do usuário no módulo TFD.
     */
    public function lockUser(User $user): JsonResponse
    {
        try {
            $userModule = $user->userModule()
                ->where('module_id', $this->module?->id)
                ->firstOrFail();

            $userModule->update([
                'is_editable' => !$userModule->is_editable,
            ]);

            $statusText = $userModule->is_editable ? 'destravado' : 'travado';

            return response()->json(['message' => "Usuário {$statusText} com sucesso."], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao travar/destravar usuário: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Alternar validação de acesso (is_valid) do usuário no módulo TFD.
     */
    public function validateUser(User $user): JsonResponse
    {
        try {
            $userModule = $user->userModule()
                ->where('module_id', $this->module?->id)
                ->firstOrFail();

            $userModule->update([
                'is_valid' => !$userModule->is_valid,
            ]);

            if (!$userModule->is_valid) {
                $user->update(['module_id' => null]);
            }

            $hasAnyValidModule = $user->userModule()->where('is_valid', true)->exists();
            $user->update(['is_valid' => $hasAnyValidModule]);

            $statusText = $userModule->is_valid ? 'validado' : 'invalidado';

            return response()->json(['message' => "Usuário {$statusText} com sucesso."], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao validar usuário: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar dados do perfil profissional associado ao usuário.
     */
    public function updateUser(User $user, Request $request): JsonResponse
    {
        try {
            $user->professional()->updateOrCreate(
                ['user_id' => $user->id],
                $this->getProfessionalPayload($request)
            );

            return response()->json(['message' => 'Usuário atualizado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao atualizar usuário: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Remover vínculo do usuário com o módulo TFD e seus dados profissionais.
     */
    public function deleteUser(User $user): JsonResponse
    {
        try {
            DB::beginTransaction();

            $userModule = $user->userModule()
                ->where('module_id', $this->module?->id)
                ->first();

            if ($userModule) {
                if ($user->module_id === $userModule->module_id) {
                    $user->update(['module_id' => null]);
                }

                $userModule->delete();
            }

            $user->professional()->delete();

            if ($user->userModule()->doesntExist()) {
                $user->update(['is_valid' => false]);
            }

            DB::commit();

            return response()->json(['message' => 'Usuário excluído com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro ao excluir usuário: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Sincronizar perfis e permissões do usuário.
     */
    public function rolesUser(User $user, Request $request): JsonResponse
    {
        try {
            $roles = Role::findMany($request->input('roles', []));
            $user->syncRoles($roles);

            return response()->json(['message' => 'Regras do usuário atualizadas com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao atualizar regras do usuário: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Extrai e formata os dados do profissional a partir do Request.
     */
    private function getProfessionalPayload(Request $request): array
    {
        return $request->only([
            'name',
            'type',
            'cns',
            'registration',
            'professional_register',
            'cbo',
        ]);
    }
}