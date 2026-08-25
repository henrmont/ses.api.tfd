<?php

namespace App\Services;

use App\Models\CostAssistance;
use App\Models\CostAssistanceDaily;
use App\Models\PatientRequest;
use App\Models\Payment;
use App\Models\Professional;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CostAssistanceService
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
     * Alternar marcação de sobrestado/paralisação da ajuda de custo.
     */
    public function haltedPatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->update([
                'is_cost_assistance_bookmark' => !$patient_request->is_cost_assistance_bookmark,
            ]);

            $this->tfd()->commit();

            $message = $patient_request->is_cost_assistance_bookmark
                ? 'Solicitação marcada em sobrestado.'
                : 'Solicitação desmarcada em sobrestado.';

            return response()->json(['message' => $message], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao alternar sobrestado da ajuda de custo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Transferir solicitação a partir do histórico para o profissional atual.
     */
    public function movePatientRequestFromHistory(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $professionalId = Professional::where('user_id', auth()->id())->value('id');

            $patient_request->update([
                'is_cost_assistance_archived' => false,
                'accountability_professional_id' => $professionalId,
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação transferida com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao mover solicitação do histórico: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Transferir solicitação a partir do setor "Outros".
     */
    public function movePatientRequestFromOthers(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $professionalId = Professional::where('user_id', auth()->id())->value('id');

            $patient_request->update([
                'cost_assistance_professional_id' => $professionalId,
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação transferida com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao mover solicitação de outros: ' . $e->getMessage());

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
                'back_to_cost_assistance' => 'Retirou do arquivo',
                'cost_assistance_professional_id' => $professionalId,
                'is_cost_assistance_archived' => false,
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
     * Arquivar etapa de ajuda de custo da solicitação.
     */
    public function archivePatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->update([
                'is_cost_assistance_archived' => true,
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação arquivada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao arquivar ajuda de custo da solicitação: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Finalizar devolução/retorno atribuído à ajuda de custo.
     */
    public function finishBackPatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->update(['back_to_cost_assistance' => null]);

            $professionalId = Professional::where('user_id', auth()->id())->value('id');

            if ($patient_request->back_from_travel == $professionalId) {
                $patient_request->update(['back_from_travel' => null]);
            }

            if ($patient_request->back_from_cost_assistance == $professionalId) {
                $patient_request->update(['back_from_cost_assistance' => null]);
            }

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação atualizada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao finalizar retorno da ajuda de custo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Tramitar a solicitação de ajuda de custo para o setor de pagamento.
     */
    public function processPatientRequestToPayment(PatientRequest $patient_request, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $payment = Payment::updateOrCreate(
                [
                    'patient_request_id' => $patient_request->id,
                    'cost_assistance_id' => $request->cost_assistance_id,
                    'travel_id'          => $request->travel_id,
                ],
                [
                    'payment_professional_id' => $request->payment_professional_id,
                ]
            );

            $payment->paymentAttachments()->delete();

            if (!empty($request->attachments)) {
                foreach ($request->attachments as $attachment) {
                    $attachmentId = $attachment['file_id'] ?? $attachment['id'] ?? null;

                    if ($attachmentId) {
                        $payment->paymentAttachments()->create([
                            'patient_request_attachment_id' => $attachmentId,
                        ]);
                    }
                }
            }

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação tramitada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao processar solicitação para pagamento: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Ajudas de Custo (CostAssistances)
    |--------------------------------------------------------------------------
    */

    /**
     * Criar registro de ajuda de custo.
     */
    public function createCostAssistance(PatientRequest $patient_request, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->costAssistances()->create($request->all());

            $this->tfd()->commit();

            return response()->json(['message' => 'Ajuda de custo criada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao criar ajuda de custo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar dados da ajuda de custo.
     */
    public function updateCostAssistance(CostAssistance $cost_assistance, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $cost_assistance->update($request->all());

            $this->tfd()->commit();

            return response()->json(['message' => 'Ajuda de custo atualizada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao atualizar ajuda de custo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Excluir registro de ajuda de custo.
     */
    public function deleteCostAssistance(CostAssistance $cost_assistance): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $cost_assistance->delete();

            $this->tfd()->commit();

            return response()->json(['message' => 'Ajuda de custo deletada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao deletar ajuda de custo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Diárias das Ajudas de Custo (CostAssistanceDailies)
    |--------------------------------------------------------------------------
    */

    /**
     * Cadastrar diária na ajuda de custo.
     */
    public function createCostAssistanceDaily(CostAssistance $cost_assistance, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $cost_assistance->costAssistanceDailies()->create($request->all());

            $this->tfd()->commit();

            return response()->json(['message' => 'Diária criada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao criar diária da ajuda de custo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar diária da ajuda de custo.
     */
    public function updateCostAssistanceDaily(CostAssistanceDaily $cost_assistance_daily, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $cost_assistance_daily->update($request->all());

            $this->tfd()->commit();

            return response()->json(['message' => 'Diária atualizada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao atualizar diária da ajuda de custo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Excluir diária da ajuda de custo.
     */
    public function deleteCostAssistanceDaily(CostAssistanceDaily $cost_assistance_daily): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $cost_assistance_daily->delete();

            $this->tfd()->commit();

            return response()->json(['message' => 'Diária deletada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao deletar diária da ajuda de custo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}