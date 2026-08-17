<?php

namespace App\Services;

use App\Models\Opinion;
use App\Models\PatientRequest;
use App\Models\Professional;
use App\Models\Report;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OpinionService
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
    | Gestão de Pareceres (Opinions)
    |--------------------------------------------------------------------------
    */

    /**
     * Criar um novo parecer para a solicitação.
     */
    public function createOpinion(PatientRequest $patient_request, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $professional = Professional::where('user_id', auth()->id())->first();

            $patient_request->opinions()->create([
                'professional_id' => $professional?->id,
                'name' => $request->name,
                'content' => $request->content,
                'is_approved' => $request->is_approved,
            ]);

            $patient_request->report()->update(['is_editable' => false]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Parecer criado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao criar parecer: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar um parecer existente.
     */
    public function updateOpinion(Opinion $opinion, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $opinion->update($request->all());

            $report = Report::find($opinion->patientRequest->report_id);
            if ($report) {
                $report->update(['is_editable' => false]);
            }

            $this->tfd()->commit();

            return response()->json(['message' => 'Parecer atualizado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao atualizar parecer: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Excluir um parecer.
     */
    public function deleteOpinion(Opinion $opinion): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $opinion->delete();

            $this->tfd()->commit();

            return response()->json(['message' => 'Parecer deletado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao deletar parecer: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Tramitação e Movimentação de Solicitações (Parecer)
    |--------------------------------------------------------------------------
    */

    /**
     * Tramitar solicitação para o profissional social.
     */
    public function processPatientRequestToSocial(PatientRequest $patient_request, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->update([
                'social_professional_id' => $request->social_professional_id,
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Tramitação feita com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao tramitar solicitação para o setor social: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Tramitar solicitação para Ajuda de Custo e Viagem.
     */
    public function processPatientRequestToCostAssistanceAndTravel(PatientRequest $patient_request, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->update([
                'cost_assistance_professional_id' => $request->cost_assistance_professional_id,
                'travel_professional_id' => $request->travel_professional_id,
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação tramitada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao tramitar solicitação para ajuda de custo e viagem: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Alternar marcação de sobrestado/paralisação (Médico ou Social).
     */
    public function haltedPatientRequest(PatientRequest $patient_request, string $type): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            if ($type === 'medical') {
                $patient_request->update(['is_medical_bookmark' => !$patient_request->is_medical_bookmark]);
                $isBookmarked = $patient_request->is_medical_bookmark;
            } else {
                $patient_request->update(['is_social_bookmark' => !$patient_request->is_social_bookmark]);
                $isBookmarked = $patient_request->is_social_bookmark;
            }

            $this->tfd()->commit();

            $message = $isBookmarked
                ? 'Solicitação marcada em sobrestado.'
                : 'Solicitação desmarcada em sobrestado.';

            return response()->json(['message' => $message], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao alternar sobrestado da solicitação: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Transferir solicitação a partir do setor de processos.
     */
    public function movePatientRequestFromProcesses(PatientRequest $patient_request, string $type): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            if ($type === 'medical') {
                $patient_request->update([
                    'social_professional_id' => null,
                ]);
            } else {
                $patient_request->update([
                    'cost_assistance_professional_id' => null,
                    'travel_professional_id' => null,
                ]);
            }

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação transferida com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao mover solicitação de processos: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Restaurar solicitação arquivada.
     */
    public function movePatientRequestFromArchive(PatientRequest $patient_request, string $type): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            if ($type === 'medical') {
                $patient_request->update([
                    'back_to_medical' => 'Retirou do arquivo',
                ]);
            } else {
                $patient_request->update([
                    'back_to_social' => 'Retirou do arquivo',
                ]);
            }

            $patient_request->update(['is_opinion_archived' => false]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação retirada do arquivo.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao mover solicitação do arquivo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Transferir solicitação a partir do setor "Outros".
     */
    public function movePatientRequestFromOthers(PatientRequest $patient_request, string $type): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $professionalId = Professional::where('user_id', auth()->id())->value('id');

            if ($type === 'medical') {
                $patient_request->update([
                    'medical_professional_id' => $professionalId,
                ]);
            } else {
                $patient_request->update([
                    'social_professional_id' => $professionalId,
                ]);
            }

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação transferida com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao mover solicitação de outros: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Finalizar devolução/retorno atribuído à solicitação.
     */
    public function finishBackPatientRequest(PatientRequest $patient_request, string $type): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            if ($type === 'medical') {
                $patient_request->update(['back_to_medical' => null]);
            } else {
                $patient_request->update(['back_to_social' => null]);
            }

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

            Log::error('Erro ao finalizar retorno da solicitação: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Arquivamento
    |--------------------------------------------------------------------------
    */

    /**
     * Arquivar parecer da solicitação.
     */
    public function archivePatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $patient_request->update(['is_opinion_archived' => true]);

            return response()->json(['message' => 'Solicitação arquivada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao arquivar parecer da solicitação: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}