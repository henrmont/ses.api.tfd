<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\PatientCare;
use App\Models\PatientRequest;
use App\Models\PatientRequestAttachment;
use App\Models\Professional;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PatientRequestService
{
    /**
     * Conexão com o banco padrão (TFD).
     */
    private function tfd(): Connection
    {
        return DB::connection();
    }

    /**
     * Conexão com o banco Storage.
     */
    private function storage(): Connection
    {
        return DB::connection('storage');
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Solicitações (TFD)
    |--------------------------------------------------------------------------
    */

    /**
     * Criar uma nova solicitação.
     */
    public function createPatientRequest(Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $ownerProfessional = Professional::where('user_id', auth()->id())->first();

            PatientRequest::create([
                'report_id' => $request->report_id,
                'type' => $request->type,
                'consultation_date' => $request->consultation_date,
                'observation' => $request->observation,
                'hospital_unity_id' => $request->hospital_unity_id,
                'owner_professional_id' => $ownerProfessional?->id,
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação criada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao criar solicitação: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Alternar marcação de sobrestado/paralisação da solicitação.
     */
    public function haltedPatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->update([
                'is_owner_bookmark' => !$patient_request->is_owner_bookmark
            ]);

            $this->tfd()->commit();

            $message = $patient_request->is_owner_bookmark
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
     * Atualizar dados da solicitação.
     */
    public function updatePatientRequest(PatientRequest $patient_request, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->update($request->except('owner_professional_id'));

            if (!$request->filled('consultation_date')) {
                $patient_request->update(['consultation_date' => null]);
            }

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação atualizada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao atualizar solicitação: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Excluir uma solicitação.
     */
    public function deletePatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->delete();

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação deletada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao deletar solicitação: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Anexos da Solicitação
    |--------------------------------------------------------------------------
    */

    /**
     * Criar anexo para uma solicitação.
     */
    public function createPatientRequestAttachment(PatientRequest $patient_request, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();
            $this->storage()->beginTransaction();

            $archiveId = $this->storeArchive($request->file('file'));

            $patient_request->attachments()->create([
                'name' => $request->name,
                'archive_id' => $archiveId,
            ]);

            $this->tfd()->commit();
            $this->storage()->commit();

            return response()->json(['message' => 'Anexo criado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            $this->storage()->rollBack();

            Log::error('Erro ao criar anexo da solicitação: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar anexo da solicitação.
     */
    public function updatePatientRequestAttachment(PatientRequestAttachment $patient_request_attachment, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();
            $this->storage()->beginTransaction();

            $payload = ['name' => $request->name];

            if ($request->hasFile('file')) {
                $payload['archive_id'] = $this->storeArchive($request->file('file'));
            }

            $patient_request_attachment->update($payload);

            $this->tfd()->commit();
            $this->storage()->commit();

            return response()->json(['message' => 'Anexo atualizado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            $this->storage()->rollBack();

            Log::error('Erro ao atualizar anexo da solicitação: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Remover anexo da solicitação.
     */
    public function deletePatientRequestAttachment(PatientRequestAttachment $patient_request_attachment): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request_attachment->delete();

            $this->tfd()->commit();

            return response()->json(['message' => 'Anexo deletado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao deletar anexo da solicitação: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Tramitação e Movimentação de Solicitações
    |--------------------------------------------------------------------------
    */

    /**
     * Encaminhar solicitação para apreciação do médico regulador.
     */
    public function processPatientRequestToMedical(PatientRequest $patient_request, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->update([
                'medical_professional_id' => $request->medical_professional_id,
            ]);

            $patient_request->report()->update(['is_editable' => false]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação tramitada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao tramitar solicitação para o médico: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Transferir solicitação a partir do setor de processos.
     */
    public function movePatientRequestFromProcesses(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->update([
                'medical_professional_id' => null,
                'back_to_medical' => null,
                'is_editable' => true
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação transferida com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao mover solicitação de processos: ' . $e->getMessage());

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

            $userProfessional = Professional::where('user_id', auth()->id())->first();

            $patient_request->update([
                'owner_professional_id' => $userProfessional?->id,
                'medical_professional_id' => null,
                'is_editable' => true
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

            $userProfessional = Professional::where('user_id', auth()->id())->first();

            $patient_request->update([
                'owner_professional_id' => $userProfessional?->id,
                'is_archived' => false
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação transferida com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao mover solicitação do arquivo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Realizar a devolução/retorno da solicitação para o setor/papel especificado.
     */
    public function undoPatientRequest(PatientRequest $patient_request, Request $request, ?string $way = null): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            switch ($request->to) {
                case 'user':
                    $patient_request->report()->update(['is_editable' => true]);
                    $patientCare = PatientCare::find($patient_request->report->patient_care_id);

                    if ($patientCare) {
                        $patientCare->update([
                            'back_to_user' => $patientCare->back_to_user . '; ' . $request->reason,
                            'is_archived' => false
                        ]);
                    }

                    if ($way) {
                        $this->changeWay($patient_request, $patient_request->owner_professional_id, $way);
                    }
                    break;

                case 'owner':
                    $patient_request->update([
                        'back_to_owner' => $patient_request->back_to_owner . '; ' . $request->reason
                    ]);

                    if ($way) {
                        $this->changeWay($patient_request, $patient_request->owner_professional_id, $way);
                    }
                    break;

                case 'medical':
                    $patient_request->update([
                        'back_to_medical' => $patient_request->back_to_medical . '; ' . $request->reason
                    ]);

                    if ($way) {
                        $this->changeWay($patient_request, $patient_request->medical_professional_id, $way);
                    }
                    break;

                case 'social':
                    $patient_request->update([
                        'back_to_social' => $patient_request->back_to_social . '; ' . $request->reason
                    ]);

                    if ($way) {
                        $this->changeWay($patient_request, $patient_request->social_professional_id, $way);
                    }
                    break;

                case 'travel':
                    $patient_request->update([
                        'back_to_travel' => $patient_request->back_to_travel . '; ' . $request->reason
                    ]);

                    if ($way) {
                        $this->changeWay($patient_request, $patient_request->travel_professional_id, $way);
                    }
                    break;

                case 'cost_assistance':
                    $patient_request->update([
                        'back_to_cost_assistance' => $patient_request->back_to_cost_assistance . '; ' . $request->reason
                    ]);

                    if ($way) {
                        $this->changeWay($patient_request, $patient_request->cost_assistance_professional_id, $way);
                    }
                    break;
            }

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação retornada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao desfazer/retornar solicitação: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Finalizar devolução/retorno atribuído à solicitação.
     */
    public function finishBackPatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $userProfessionalId = Professional::where('user_id', auth()->id())->value('id');

            $patient_request->update(['back_to_owner' => null]);

            if ($patient_request->back_from_travel == $userProfessionalId) {
                $patient_request->update(['back_from_travel' => null]);
            }

            if ($patient_request->back_from_cost_assistance == $userProfessionalId) {
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
    | Arquivamento de Etapas
    |--------------------------------------------------------------------------
    */

    /**
     * Arquivar parecer da solicitação.
     */
    public function archiveOpinionPatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $patient_request->update(['is_opinion_archived' => true]);

            return response()->json(['message' => 'Solicitação arquivada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao arquivar parecer da solicitação: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Arquivar etapa de viagem da solicitação.
     */
    public function archiveTravelPatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $patient_request->update(['is_travel_archived' => true]);

            return response()->json(['message' => 'Solicitação arquivada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao arquivar viagem da solicitação: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Arquivar prestação de contas da solicitação.
     */
    public function archiveAccountabilityPatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $patient_request->update(['is_accountability_archived' => true]);

            return response()->json(['message' => 'Solicitação arquivada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao arquivar prestação de contas da solicitação: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Arquivar etapa de pagamento da solicitação.
     */
    public function archivePaymentPatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $patient_request->update(['is_payment_archived' => true]);

            return response()->json(['message' => 'Solicitação arquivada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao arquivar pagamento da solicitação: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos Auxiliares Privados
    |--------------------------------------------------------------------------
    */

    /**
     * Salva o arquivo no banco de storage codificado em Base64 Data URI.
     */
    private function storeArchive(UploadedFile $file): int
    {
        $fileContents = file_get_contents($file->getRealPath());
        $base64String = base64_encode($fileContents);
        $mimeType = $file->getClientMimeType();
        $dataUri = "data:{$mimeType};base64,{$base64String}";

        $archive = Archive::on('storage')->create(['archive' => $dataUri]);

        return $archive->id;
    }

    /**
     * Altera a origem do retorno do fluxo de tramitação.
     */
    private function changeWay(PatientRequest $patient_request, ?int $professionalId, string $way): void
    {
        if (!$professionalId) {
            return;
        }

        switch ($way) {
            case 'travel':
                $patient_request->update(['back_from_travel' => $professionalId]);
                break;
            case 'cost assistance':
                $patient_request->update(['back_from_cost_assistance' => $professionalId]);
                break;
        }
    }
}