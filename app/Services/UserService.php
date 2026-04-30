<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Professional;
use App\Models\User;
use App\Models\UserModule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService
{
    private $_module;

    public function __construct() {
        $this->_module = Module::where('name','tfd')->first();
    }

    public function createUser(Request $request)
    {
        try {
            DB::beginTransaction();
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                $user = User::on('auth')->create([
                    'email' => $request->email,
                    'name' => $request->email,
                    'password' => Hash::make('12345678'),
                    'module_id' => $this->_module->id,
                ]);
                $user->userModule()->create(['module_id' => $this->_module->id]);
                Professional::create([
                    'user_id' => $user->id,
                    'name' => $request->name,
                    'type' => $request->type,
                    'cns' => $request->cns,
                    'registration' => $request->registration,
                    'professional_register' => $request->professional_register,
                    'cbo' => $request->cbo,
                ]);
            } else {
                $user->update(['is_valid' => true]);
                UserModule::on('auth')->create([
                    'user_id' => $user->id,
                    'module_id' => $this->_module->id
                ]);
                Professional::create([
                    'user_id' => $user->id,
                    'name' => $request->name,
                    'type' => $request->type,
                    'cns' => $request->cns,
                    'registration' => $request->registration,
                    'professional_register' => $request->professional_register,
                    'cbo' => $request->cbo,
                ]);
            }
            DB::commit();
            return response()->json(['message' => 'Usuário criado com sucesso.'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function lockUser(User $user)
    {
        try {
            $user_module = $user->userModule()->where('module_id',$this->_module->id)->first();
            $user_module->update(['is_editable' => !$user_module->is_editable]);
            return response()->json(['message' => 'Usuário '.($user_module->is_editable ? 'destravado' : 'travado').' com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function validateUser(User $user)
    {
        try {
            $user_module = $user->userModule()->where('module_id',$this->_module->id)->first();
            $user_module->update(['is_valid' => !$user_module->is_valid]);
            return response()->json(['message' => 'Usuário '.($user_module->is_valid ? 'validado' : 'invalidado').' com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateUser(User $user, Request $request)
    {
        try {
            if ($user->professional()->exists()) {
                $user->professional()->update([
                    'name' => $request->name,
                    'type' => $request->type,
                    'cns' => $request->cns,
                    'registration' => $request->registration,
                    'professional_register' => $request->professional_register,
                    'cbo' => $request->cbo,
                ]);
            } else {
                $user->professional()->create([
                    'name' => $request->name,
                    'type' => $request->type,
                    'cns' => $request->cns,
                    'registration' => $request->registration,
                    'professional_register' => $request->professional_register,
                    'cbo' => $request->cbo,
                ]);
            }
            return response()->json(['message' => 'Usuário atualizado com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteUser(User $user)
    {
        try {
            DB::beginTransaction();
            $user_module = $user->userModule()->where('module_id',$this->_module->id)->first();
            if ($user->module_id == $user_module->module_id)
                $user->update(['module_id' => null]);
            $user_module->delete();
            $user->professional()->delete();
            if ($user->userModule()->doesntExist())
                $user->update(['is_valid' => false]);
            DB::commit();
            return response()->json(['message' => 'Usuário excluído com sucesso.'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function rolesUser(User $user, Request $request)
    {
        try {
            $user->syncRoles(Role::findMany($request->roles));
            return response()->json(['message' => 'Regras do usuário atualizadas com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}