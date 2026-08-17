<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\Escort;
use App\Models\Patient;
use App\Models\PatientCare;
use App\Models\PatientCareEscort;
use App\Models\PatientInfo;
use App\Models\Report;
use App\Models\ReportAttachment;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PatientService
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
    private function core(): Connection
    {
        return DB::connection('core');
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
    | Gestão de Pacientes e Atendimentos
    |--------------------------------------------------------------------------
    */

    /**
     * Criar um novo paciente e seu atendimento (PatientCare).
     */
    public function createPatient(Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();
            $this->core()->beginTransaction();
            $this->storage()->beginTransaction();

            $patient = Patient::on('core')
                ->where('cns', $request->cns)
                ->orWhere('document', $request->document)
                ->first();

            if ($patient) {
                $patient->update($request->all());
            } else {
                $patient = Patient::on('core')->create(
                    $request->except(['observation', 'control_number'])
                );
            }

            PatientCare::on('core')->create([
                'patient_id' => $patient->id,
                'module_id' => auth()->user()->module_id,
                'user_id' => auth()->user()->id,
            ]);

            $this->processFileAttachments($patient, $request, [
                'file_cns' => 'file_cns_id',
                'file_document' => 'file_document_id',
                'file_deficiency' => 'file_deficiency_id',
                'file_address' => 'file_address_id',
            ]);

            $patientInfo = PatientInfo::create([
                'patient_id' => $patient->id,
                'observation' => $request->observation,
                'control_number' => $request->control_number,
            ]);

            $this->processFileAttachments($patientInfo, $request, [
                'file_protocol' => 'file_protocol_id',
            ]);

            $this->tfd()->commit();
            $this->core()->commit();
            $this->storage()->commit();

            return response()->json(['message' => 'Paciente cadastrado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            $this->core()->rollBack();
            $this->storage()->rollBack();

            Log::error('Erro ao cadastrar paciente: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar dados cadastrais e anexos do paciente/atendimento.
     */
    public function updatePatient(PatientCare $patient_care, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();
            $this->core()->beginTransaction();
            $this->storage()->beginTransaction();

            $patient = $patient_care->patient;
            $patient->update($request->except(['observation', 'control_number']));

            $this->processFileAttachments($patient, $request, [
                'file_cns' => 'file_cns_id',
                'file_document' => 'file_document_id',
                'file_deficiency' => 'file_deficiency_id',
                'file_address' => 'file_address_id',
            ]);

            if ($patient->patientInfo) {
                $patient->patientInfo()->update($request->only(['observation', 'control_number']));

                $this->processFileAttachments($patient->patientInfo, $request, [
                    'file_protocol' => 'file_protocol_id',
                ]);
            }

            $this->tfd()->commit();
            $this->core()->commit();
            $this->storage()->commit();

            return response()->json(['message' => 'Paciente atualizado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            $this->core()->rollBack();
            $this->storage()->rollBack();

            Log::error('Erro ao atualizar paciente: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Acompanhantes
    |--------------------------------------------------------------------------
    */

    /**
     * Cadastrar e vincular um acompanhante ao atendimento.
     */
    public function createEscort(PatientCare $patient_care, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();
            $this->storage()->beginTransaction();

            $escort = Escort::where('cns', $request->cns)
                ->orWhere('document', $request->document)
                ->first();

            if ($escort) {
                $escort->update($request->all());
                $escort->patientCareEscort()->create([
                    'patient_care_id' => $patient_care->id,
                ]);
            } else {
                $escort = $patient_care->escorts()->create($request->all());
            }

            $this->processFileAttachments($escort, $request, [
                'file_cns' => 'file_cns_id',
                'file_document' => 'file_document_id',
                'file_address' => 'file_address_id',
            ]);

            $this->tfd()->commit();
            $this->storage()->commit();

            return response()->json(['message' => 'Acompanhante criado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            $this->storage()->rollBack();

            Log::error('Erro ao criar acompanhante: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar dados e anexos do acompanhante.
     */
    public function updateEscort(Escort $escort, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();
            $this->storage()->beginTransaction();

            $escort->update($request->all());

            $this->processFileAttachments($escort, $request, [
                'file_cns' => 'file_cns_id',
                'file_document' => 'file_document_id',
                'file_address' => 'file_address_id',
            ]);

            $this->tfd()->commit();
            $this->storage()->commit();

            return response()->json(['message' => 'Acompanhante atualizado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            $this->storage()->rollBack();

            Log::error('Erro ao atualizar acompanhante: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Remover vínculo do acompanhante com o atendimento.
     */
    public function deleteEscort(PatientCareEscort $patient_care_escort): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_care_escort->delete();

            $this->tfd()->commit();

            return response()->json(['message' => 'Acompanhante deletado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao deletar acompanhante: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Laudos
    |--------------------------------------------------------------------------
    */

    /**
     * Registrar um novo laudo no atendimento.
     */
    public function createReport(PatientCare $patient_care, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_care->reports()->create($request->all());

            $this->tfd()->commit();

            return response()->json(['message' => 'Laudo criado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao criar laudo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar informações do laudo.
     */
    public function updateReport(Report $report, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $report->update($request->all());

            $this->tfd()->commit();

            return response()->json(['message' => 'Laudo atualizado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao atualizar laudo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Excluir laudo do atendimento.
     */
    public function deleteReport(Report $report): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $report->delete();

            $this->tfd()->commit();

            return response()->json(['message' => 'Laudo deletado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao deletar laudo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Anexos do Laudo
    |--------------------------------------------------------------------------
    */

    /**
     * Anexar um arquivo ao laudo.
     */
    public function createReportAttachment(Report $report, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();
            $this->storage()->beginTransaction();

            $archiveId = $this->storeArchive($request->file('file'));

            $report->attachments()->create([
                'name' => $request->name,
                'archive_id' => $archiveId,
            ]);

            $this->tfd()->commit();
            $this->storage()->commit();

            return response()->json(['message' => 'Anexo criado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            $this->storage()->rollBack();

            Log::error('Erro ao criar anexo do laudo: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar dados ou o arquivo do anexo do laudo.
     */
    public function updateReportAttachment(ReportAttachment $report_attachment, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();
            $this->storage()->beginTransaction();

            $payload = ['name' => $request->name];

            if ($request->hasFile('file')) {
                $payload['archive_id'] = $this->storeArchive($request->file('file'));
            }

            $report_attachment->update($payload);

            $this->tfd()->commit();
            $this->storage()->commit();

            return response()->json(['message' => 'Anexo atualizado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            $this->storage()->rollBack();

            Log::error('Erro ao atualizar anexo do laudo: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Remover um anexo do laudo.
     */
    public function deleteReportAttachment(ReportAttachment $report_attachment): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $report_attachment->delete();

            $this->tfd()->commit();

            return response()->json(['message' => 'Anexo deletado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao deletar anexo do laudo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Alteração de Estado e Movimentações
    |--------------------------------------------------------------------------
    */

    /**
     * Arquivar atendimento do paciente.
     */
    public function archivePatient(PatientCare $patient_care): JsonResponse
    {
        try {
            $patient_care->update(['is_archived' => true]);

            return response()->json(['message' => 'Paciente arquivado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao arquivar paciente: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Retirar atendimento do arquivo e atribuir ao usuário autenticado.
     */
    public function movePatientFromArchive(PatientCare $patient_care): JsonResponse
    {
        try {
            $patient_care->update([
                'is_archived' => false,
                'user_id' => auth()->user()->id,
                'back_to_user' => 'Retirou do arquivo',
            ]);

            return response()->json(['message' => 'Paciente transferido para sua caixa.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao transferir paciente do arquivo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Transferir atendimento do setor "Outros" para a caixa do usuário autenticado.
     */
    public function movePatientFromOthers(PatientCare $patient_care): JsonResponse
    {
        try {
            $patient_care->update([
                'user_id' => auth()->user()->id,
            ]);

            return response()->json(['message' => 'Paciente transferido para sua caixa.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao transferir paciente do setor outros: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Alternar validação do atendimento do paciente.
     */
    public function validatePatient(PatientCare $patient_care): JsonResponse
    {
        try {
            $patient_care->update(['is_valid' => !$patient_care->is_valid]);
            $statusText = $patient_care->is_valid ? 'validado' : 'invalidado';

            return response()->json(['message' => "Paciente {$statusText} com sucesso."], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao validar/invalidar paciente: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Finalizar retorno pendente do paciente.
     */
    public function finishBackPatient(PatientCare $patient_care): JsonResponse
    {
        try {
            $patient_care->update(['back_to_user' => null]);

            return response()->json(['message' => 'Paciente atualizado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao finalizar retorno do paciente: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Cálculos Financeiros
    |--------------------------------------------------------------------------
    */

    /**
     * Calcular o saldo referente aos custos e prestação de contas do atendimento.
     */
    public function calcBalance(PatientCare $patient_care): JsonResponse
    {
        try {
            $totalCosts = $patient_care->reports
                ->flatMap->patientRequests
                ->flatMap->costAssistances
                ->flatMap->costAssistanceDailies
                ->sum(fn ($daily) => $daily->amount * optional($daily->dailyCost)->value);

            $totalAccountabilities = $patient_care->reports
                ->flatMap->patientRequests
                ->flatMap->accountabilities
                ->flatMap->accountabilityDailies
                ->sum(fn ($daily) => $daily->amount * optional($daily->dailyCost)->value);

            $balance = $totalCosts - $totalAccountabilities;

            return response()->json($balance, JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao calcular saldo do paciente: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos Auxiliares Privados
    |--------------------------------------------------------------------------
    */

    /**
     * Processa o upload de arquivos para um determinado modelo.
     */
    private function processFileAttachments($model, Request $request, array $fileFields): void
    {
        $updates = [];

        foreach ($fileFields as $requestKey => $modelColumn) {
            if ($request->hasFile($requestKey)) {
                $updates[$modelColumn] = $this->storeArchive($request->file($requestKey));
            }
        }

        if (!empty($updates)) {
            $model->update($updates);
        }
    }

    /**
     * Armazena um arquivo como Base64 no banco de dados de storage.
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
}