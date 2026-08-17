<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Listar usuários do sistema TFD.
     */
    public function getUsers(): JsonResponse
    {
        $this->authorize('tfd/usuário listar');

        $users = User::query()
            ->tfd()
            ->with(['roles', 'professional'])
            ->where('email', '!=', 'admin@tfd.com')
            ->latest('id')
            ->get();

        return response()->json($users, JsonResponse::HTTP_OK);
    }

    /**
     * Listar perfis/roles do TFD.
     */
    public function getRoles(): JsonResponse
    {
        $this->authorize('tfd/usuário listar');

        $roles = Role::query()
            ->with('permissions')
            ->where('name', 'LIKE', 'tfd%')
            ->get();

        return response()->json($roles, JsonResponse::HTTP_OK);
    }

    /**
     * Criar um novo usuário.
     */
    public function createUser(Request $request)
    {
        $this->authorize('tfd/usuário criar');

        return $this->userService->createUser($request);
    }

    /**
     * Travar/Destravar edição de um usuário.
     */
    public function lockUser(User $user)
    {
        $this->authorize('tfd/usuário travar');

        return $this->userService->lockUser($user);
    }

    /**
     * Validar/Invalidar status de um usuário.
     */
    public function validateUser(User $user)
    {
        $this->authorize('tfd/usuário validar');

        return $this->userService->validateUser($user);
    }

    /**
     * Atualizar dados de um usuário.
     */
    public function updateUser(User $user, Request $request)
    {
        $this->authorize('tfd/usuário atualizar');

        return $this->userService->updateUser($user, $request);
    }

    /**
     * Excluir um usuário.
     */
    public function deleteUser(User $user)
    {
        $this->authorize('tfd/usuário deletar');

        return $this->userService->deleteUser($user);
    }

    /**
     * Atualizar as regras/permissões atribuídas ao usuário.
     */
    public function rolesUser(User $user, Request $request)
    {
        $this->authorize('tfd/usuário atualizar');

        return $this->userService->rolesUser($user, $request);
    }

    /*
    |--------------------------------------------------------------------------
    | Validadores Assíncronos (Existência de Registros)
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica se o e-mail informado já existe no banco.
     */
    public function emailUserExists(string $email, ?string $currentEmail = null): JsonResponse
    {
        $this->authorize('tfd/usuário listar');

        $exists = User::query()
            ->tfd()
            ->where('email', $email)
            ->when($currentEmail, fn ($query) => $query->where('email', '!=', $currentEmail))
            ->exists();

        return response()->json($exists, JsonResponse::HTTP_OK);
    }

    /**
     * Verifica se o CNS informado já existe no banco.
     */
    public function cnsUserExists(string $cns, ?string $currentCns = null): JsonResponse
    {
        $this->authorize('tfd/usuário listar');

        $exists = Professional::query()
            ->where('cns', $cns)
            ->when($currentCns, fn ($query) => $query->where('cns', '!=', $currentCns))
            ->exists();

        return response()->json($exists, JsonResponse::HTTP_OK);
    }
}