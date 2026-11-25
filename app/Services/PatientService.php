<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\Escort;
use App\Models\Module;
use App\Models\Patient;
use App\Models\PatientCare;
use App\Models\PatientCareEscort;
use App\Models\PatientInfo;
use App\Models\Report;
use App\Models\ReportAttachment;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
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

    protected ?Module $module;

    public function __construct()
    {
        $this->module = Module::where('name', 'tfd')->first();
    }

    /**
     * Criar um novo paciente e seu atendimento (PatientCare).
     */
    public function createPatient(Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();
            $this->core()->beginTransaction();
            $this->storage()->beginTransaction();

            $patientPayload = $this->getPatientPayload($request);

            $patient = Patient::on('core')
                ->where(function ($query) use ($patientPayload) {
                    if (!empty($patientPayload['cns'])) {
                        $query->where('cns', $patientPayload['cns']);
                    }
                    if (!empty($patientPayload['document'])) {
                        $query->orWhere('document', $patientPayload['document']);
                    }
                })
                ->first();

            if ($patient) {
                $patient->update($patientPayload);
                $patientId = $patient->id;
            } else {
                $patientId = $this->core()->table('patients')->insertGetId($patientPayload);
                $patient = Patient::on('core')->findOrFail($patientId);
            }

            $this->core()
                ->table('patient_cares')
                ->insertGetId([
                    'patient_id' => $patientId,
                    'module_id'  => auth()->user()->module_id,
                    'user_id'    => auth()->user()->id,
                ]);

            $this->processFileAttachments($patient, $request, [
                'file_cns'        => 'file_cns_id',
                'file_document'   => 'file_document_id',
                'file_deficiency' => 'file_deficiency_id',
                'file_address'    => 'file_address_id',
            ]);

            $patientInfo = PatientInfo::query()->updateOrCreate(
                ['patient_id' => $patientId],
                $request->only(['observation', 'control_number', 'sigadoc'])
            );

            $this->processFileAttachments($patientInfo, $request, [
                'file_sigadoc' => 'file_sigadoc_id',
            ]);

            $this->tfd()->commit();
            $this->core()->commit();
            $this->storage()->commit();

            return response()->json(['message' => 'Paciente cadastrado com sucesso.'], JsonResponse::HTTP_CREATED);
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
            $patient->update($this->getPatientPayload($request));

            $this->processFileAttachments($patient, $request, [
                'file_cns'        => 'file_cns_id',
                'file_document'   => 'file_document_id',
                'file_deficiency' => 'file_deficiency_id',
                'file_address'    => 'file_address_id',
            ]);

            $patientInfo = PatientInfo::query()->updateOrCreate(
                ['patient_id' => $patient->id],
                $request->only(['observation', 'control_number', 'sigadoc'])
            );

            $this->processFileAttachments($patientInfo, $request, [
                'file_sigadoc' => 'file_sigadoc_id',
            ]);

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

    /**
     * Arquivar atendimento do paciente.
     */
    public function archivePatient(PatientCare $patient_care): JsonResponse
    {
        try {
            $this->core()->beginTransaction();
            $patient_care->update(['is_archived' => true]);
            $this->core()->commit();

            return response()->json(['message' => 'Paciente arquivado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->core()->rollBack();
            Log::error('Erro ao arquivar paciente: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Retirar atendimento do arquivo e atribuir ao usuário autenticado.
     */
    public function movePatientFromArchive(PatientCare $patient_care): JsonResponse
    {
        try {
            $this->core()->beginTransaction();
            $patient_care->update([
                'is_archived'  => false,
                'user_id'      => auth()->user()->id,
                'back_to_user' => 'Retirou do arquivo',
            ]);
            $this->core()->commit();

            return response()->json(['message' => 'Paciente transferido para sua caixa.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->core()->rollBack();
            Log::error('Erro ao transferir paciente do arquivo: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Transferir atendimento do setor "Outros" para a caixa do usuário autenticado.
     */
    public function movePatientFromOthers(PatientCare $patient_care): JsonResponse
    {
        try {
            $this->core()->beginTransaction();
            $patient_care->update([
                'user_id' => auth()->user()->id,
            ]);
            $this->core()->commit();

            return response()->json(['message' => 'Paciente transferido para sua caixa.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->core()->rollBack();
            Log::error('Erro ao transferir paciente do setor outros: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Alternar validação do atendimento do paciente.
     */
    public function validatePatient(PatientCare $patient_care): JsonResponse
    {
        try {
            $this->core()->beginTransaction();
            $patient_care->update(['is_valid' => !$patient_care->is_valid]);
            $this->core()->commit();

            $statusText = $patient_care->is_valid ? 'validado' : 'invalidado';

            return response()->json(['message' => "Paciente {$statusText} com sucesso."], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->core()->rollBack();
            Log::error('Erro ao validar paciente: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Finalizar retorno de parecer do paciente.
     */
    public function finishBackPatient(PatientCare $patient_care): JsonResponse
    {
        try {
            $this->core()->beginTransaction();
            $patient_care->update(['back_to_user' => null]);
            $this->core()->commit();

            return response()->json(['message' => 'Retorno finalizado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->core()->rollBack();
            Log::error('Erro ao finalizar retorno do paciente: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Cadastrar e vincular um acompanhante ao atendimento.
     */
    public function createEscort(PatientCare $patient_care, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();
            $this->storage()->beginTransaction();

            $escortPayload = $this->getEscortPayload($request);

            $escort = Escort::query()
                ->where(function ($query) use ($escortPayload) {
                    if (!empty($escortPayload['cns'])) {
                        $query->where('cns', $escortPayload['cns']);
                    }
                    if (!empty($escortPayload['document'])) {
                        $query->orWhere('document', $escortPayload['document']);
                    }
                })
                ->first();

            if ($escort) {
                $escort->update($escortPayload);
                $escort->patientCareEscort()->firstOrCreate([
                    'patient_care_id' => $patient_care->id,
                ]);
            } else {
                $escort = $patient_care->escorts()->create($escortPayload);
            }

            $this->processFileAttachments($escort, $request, [
                'file_cns'      => 'file_cns_id',
                'file_document' => 'file_document_id',
                'file_address'  => 'file_address_id',
            ]);

            $this->tfd()->commit();
            $this->storage()->commit();

            return response()->json(['message' => 'Acompanhante criado com sucesso.'], JsonResponse::HTTP_CREATED);
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

            $escort->update($this->getEscortPayload($request));

            $this->processFileAttachments($escort, $request, [
                'file_cns'      => 'file_cns_id',
                'file_document' => 'file_document_id',
                'file_address'  => 'file_address_id',
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
            Log::error('Erro ao deletar acompanhante: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Registrar um novo laudo no atendimento.
     */
    public function createReport(PatientCare $patient_care, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_care->reports()->create($this->getReportPayload($request));

            $this->tfd()->commit();

            return response()->json(['message' => 'Laudo criado com sucesso.'], JsonResponse::HTTP_CREATED);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            Log::error('Erro ao criar laudo: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

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

            $report->update($this->getReportPayload($request));

            $this->tfd()->commit();

            return response()->json(['message' => 'Laudo atualizado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            Log::error('Erro ao atualizar laudo: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

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
            Log::error('Erro ao deletar laudo: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

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
                'name'       => $request->input('name'),
                'archive_id' => $archiveId,
            ]);

            $this->tfd()->commit();
            $this->storage()->commit();

            return response()->json(['message' => 'Anexo criado com sucesso.'], JsonResponse::HTTP_CREATED);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            $this->storage()->rollBack();

            Log::error('Erro ao criar anexo do laudo: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar um anexo do laudo.
     */
    public function updateReportAttachment(ReportAttachment $attachment, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();
            $this->storage()->beginTransaction();

            $data = [
                'name' => $request->input('name'),
            ];

            if ($request->hasFile('file')) {
                $data['archive_id'] = $this->storeArchive($request->file('file'), $attachment->archive_id);
            }

            $attachment->update($data);

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
            Log::error('Erro ao deletar anexo do laudo: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Monta o payload de dados cadastrais do paciente.
     */
    private function getPatientPayload(Request $request): array
    {
        return [
            'name'           => $request->name,
            'cns'            => $this->onlyDigits($request->cns),
            'document_type'  => $request->document_type,
            'document'       => $this->onlyDigits($request->document),
            'birth_date'     => $request->birth_date,
            'gender'         => $request->gender,
            'newborn'        => $request->newborn,
            'race'           => $request->race,
            'ethnicity'      => $request->ethnicity,
            'marital_status' => $request->marital_status,
            'mother_name'    => $request->mother_name,
            'father_name'    => $request->father_name,
            'naturalness'    => $request->naturalness,
            'phone'          => $this->onlyDigits($request->phone),
            'cell_phone'     => $this->onlyDigits($request->cell_phone),
            'email'          => $request->email,
            'profession'     => $request->profession,
            'deficiency'     => $request->deficiency,
            'cep'            => $this->onlyDigits($request->cep),
            'address'        => $request->address,
            'number'         => $request->number,
            'complement'     => $request->complement,
            'neighborhood'   => $request->neighborhood,
            'city'           => $request->city,
            'state'          => $request->state,
        ];
    }

    /**
     * Monta o payload de dados cadastrais do acompanhante.
     */
    private function getEscortPayload(Request $request): array
    {
        return [
            'cns'             => $this->onlyDigits($request->cns),
            'document'        => $this->onlyDigits($request->document),
            'name'            => $request->name,
            'relation'        => $request->relation,
            'birth_date'      => $request->birth_date,
            'gender'          => $request->gender,
            'is_same_address' => $request->is_same_address,
            'cep'             => $this->onlyDigits($request->cep),
            'address'         => $request->address,
            'number'          => $request->number,
            'complement'      => $request->complement,
            'neighborhood'    => $request->neighborhood,
            'city'            => $request->city,
            'state'           => $request->state,
        ];
    }

    /**
     * Monta o payload de dados do laudo.
     */
    protected function getReportPayload(Request $request): array
    {
        return [
            'protocol' => $request->input('protocol'),
            'specialty' => $request->input('specialty'),
            'cid_id' => $request->input('cid_id'),
            'lawsuit' => $request->boolean('lawsuit'),
            'diagnosis' => $request->input('diagnosis'),
            'is_editable' => true,
            'is_export' => false,
        ];
    }

    /**
     * Remove caracteres não numéricos quando o valor existir.
     */
    private function onlyDigits(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return preg_replace('/\D/', '', (string) $value);
    }

    /**
     * Processa múltiplos uploads de arquivos e vincula seus archive_id no model.
     */
    private function processFileAttachments(Model $model, Request $request, array $fileMap): void
    {
        $updates = [];

        foreach ($fileMap as $inputKey => $columnKey) {
            if ($request->hasFile($inputKey)) {
                $updates[$columnKey] = $this->storeArchive($request->file($inputKey));
            }
        }

        if (!empty($updates)) {
            $model->update($updates);
        }
    }

    /**
     * Salva o arquivo no banco de storage codificado em Base64 Data URI.
     * Se informado $previousArchiveId, vincula o arquivo substituído ao novo id via ref_id.
     */
    private function storeArchive(UploadedFile $file, ?int $previousArchiveId = null): int
    {
        $fileContents = file_get_contents($file->getRealPath());
        $base64String = base64_encode($fileContents);
        $mimeType = $file->getClientMimeType();
        $dataUri = "data:{$mimeType};base64,{$base64String}";

        $archive = Archive::on('storage')->create(['archive' => $dataUri]);

        if ($previousArchiveId) {
            Archive::on('storage')
                ->where('id', $previousArchiveId)
                ->update(['ref_id' => $archive->id]);
        }

        return $archive->id;
    }
}