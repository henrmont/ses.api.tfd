<?php

namespace App\Services;

use App\Models\Accountability;
use App\Models\AccountabilityDaily;
use App\Models\PatientRequest;
use App\Models\Professional;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountabilityService
{
    /**
     * Conexão com o banco padrão (TFD).
     */
    private function tfd(): Connection
    {
        return DB::connection();
    }

    /*
    |--------------------------------------------------------------------------
    | Tramitação e Mudança de Estados da Solicitação
    |--------------------------------------------------------------------------
    */

    /**
     * Alternar marcação de sobrestado/paralisação da prestação de contas.
     */
    public function haltedPatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->update([
                'is_accountability_bookmark' => !$patient_request->is_accountability_bookmark,
            ]);

            $this->tfd()->commit();

            $message = $patient_request->is_accountability_bookmark
                ? 'Solicitação marcada em sobrestado.'
                : 'Solicitação desmarcada em sobrestado.';

            return response()->json(['message' => $message], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao alternar sobrestado da prestação de contas: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Transferir solicitação para o profissional de prestação de contas atual.
     */
    public function movePatientRequestFromOthers(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $professionalId = Professional::where('user_id', auth()->id())->value('id');

            $patient_request->update([
                'accountability_professional_id' => $professionalId,
                'is_cost_assistance_archived' => false,
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação transferida com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao mover solicitação na prestação de contas: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Restaurar solicitação arquivada.
     */
    public function movePatientRequestFromArchive(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $professionalId = Professional::where('user_id', auth()->id())->value('id');

            $patient_request->update([
                'back_to_accountability' => 'Retirou do arquivo',
                'accountability_professional_id' => $professionalId,
                'is_accountability_archived' => false,
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação retirada do arquivo.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao mover solicitação do arquivo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Arquivar a etapa da solicitação.
     */
    public function archivePatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->update([
                'is_accountability_archived' => true,
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação arquivada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao arquivar prestação de contas da solicitação: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Prestações de Contas (Accountabilities)
    |--------------------------------------------------------------------------
    */

    /**
     * Criar registro de prestação de contas.
     */
    public function createAccountability(PatientRequest $patient_request, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->accountabilities()->create($request->all());

            $this->tfd()->commit();

            return response()->json(['message' => 'Prestação de conta criada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao criar prestação de contas: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar registro de prestação de contas.
     */
    public function updateAccountability(Accountability $accountability, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $accountability->update($request->all());

            $this->tfd()->commit();

            return response()->json(['message' => 'Prestação de conta atualizada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao atualizar prestação de contas: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Excluir registro de prestação de contas.
     */
    public function deleteAccountability(Accountability $accountability): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $accountability->delete();

            $this->tfd()->commit();

            return response()->json(['message' => 'Prestação de conta deletada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao deletar prestação de contas: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Diárias da Prestação de Contas (AccountabilityDailies)
    |--------------------------------------------------------------------------
    */

    /**
     * Cadastrar diária na prestação de contas.
     */
    public function createAccountabilityDaily(Accountability $accountability, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $accountability->accountabilityDailies()->create($request->all());

            $this->tfd()->commit();

            return response()->json(['message' => 'Diária criada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao criar diária da prestação de contas: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar diária da prestação de contas.
     */
    public function updateAccountabilityDaily(AccountabilityDaily $accountability_daily, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $accountability_daily->update($request->all());

            $this->tfd()->commit();

            return response()->json(['message' => 'Diária atualizada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao atualizar diária da prestação de contas: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Excluir diária da prestação de contas.
     */
    public function deleteAccountabilityDaily(AccountabilityDaily $accountability_daily): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $accountability_daily->delete();

            $this->tfd()->commit();

            return response()->json(['message' => 'Diária deletada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao deletar diária da prestação de contas: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}