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
     * Retorna o usuário autenticado com seus relacionamentos.
     */
    public function getMe(): JsonResponse
    {
        $me = User::with('roles.permissions', 'professional.types')->find(auth()->id());

        return response()->json($me, JsonResponse::HTTP_OK);
    }

    /**
     * Listar usuários do sistema TFD.
     */
    public function getUsers(): JsonResponse
    {
        $this->authorize('tfd/usuário listar');

        $users = User::query()
            ->tfd()
            ->with(['roles', 'professional.types'])
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
    public function createUser(Request $request): JsonResponse
    {
        $this->authorize('tfd/usuário criar');

        return $this->userService->createUser($request);
    }

    /**
     * Atualizar dados completos de um usuário (PUT).
     */
    public function updateUser(User $user, Request $request): JsonResponse
    {
        $this->authorize('tfd/usuário atualizar');

        return $this->userService->updateUser($user, $request);
    }

    /**
     * Alternar estado de bloqueio/edição do usuário no módulo (PATCH /{user}/lock).
     */
    public function lockUser(User $user): JsonResponse
    {
        $this->authorize('tfd/usuário travar');

        return $this->userService->lockUser($user);
    }

    /**
     * Alternar estado de validação do usuário no módulo (PATCH /{user}/validate).
     */
    public function validateUser(User $user): JsonResponse
    {
        $this->authorize('tfd/usuário validar');

        return $this->userService->validateUser($user);
    }

    /**
     * Excluir um usuário.
     */
    public function deleteUser(User $user): JsonResponse
    {
        $this->authorize('tfd/usuário deletar');

        return $this->userService->deleteUser($user);
    }

    /**
     * Sincronizar as regras/permissões atribuídas ao usuário (PUT /users/{user}/roles).
     */
    public function rolesUser(User $user, Request $request): JsonResponse
    {
        $this->authorize('tfd/usuário atualizar');

        return $this->userService->rolesUser($user, $request);
    }

    /*
    |--------------------------------------------------------------------------
    | Validadores Assíncronos (Disponibilidade de Registros via Query Params)
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica disponibilidade de e-mail ou CNS via query string.
     * GET /tfd/users/availability?email=...&ignore=...
     * GET /tfd/users/availability?cns=...&ignore=...
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $this->authorize('tfd/usuário listar');

        $ignore = $request->query('ignore');

        if ($request->filled('email')) {
            $email = strtolower(trim($request->query('email')));
            $cleanIgnore = $ignore ? strtolower(trim($ignore)) : null;

            $exists = User::query()
                ->tfd()
                ->where('email', $email)
                ->when($cleanIgnore, fn ($query) => $query->where('email', '!=', $cleanIgnore))
                ->exists();

            return response()->json([
                'available' => !$exists,
                'exists'    => $exists,
            ], JsonResponse::HTTP_OK);
        }

        if ($request->filled('cns')) {
            $cns = preg_replace('/\D/', '', $request->query('cns'));
            $cleanIgnore = $ignore ? preg_replace('/\D/', '', $ignore) : null;

            $exists = Professional::query()
                ->where('cns', $cns)
                ->when($cleanIgnore, fn ($query) => $query->where('cns', '!=', $cleanIgnore))
                ->exists();

            return response()->json([
                'available' => !$exists,
                'exists'    => $exists,
            ], JsonResponse::HTTP_OK);
        }

        return response()->json([
            'message' => 'Informe o parâmetro email ou cns para verificação.',
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}