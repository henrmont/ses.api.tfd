<?php

namespace App\Http\Controllers;

use App\Models\Cid;
use App\Models\Escort;
use App\Models\Patient;
use App\Models\PatientCare;
use App\Models\PatientCareEscort;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Services\PatientService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected PatientService $patientService
    ) {}

    /**
     * Listar pacientes/atendimentos ativos do TFD.
     */
    public function getPatients(): JsonResponse
    {
        $this->authorize('tfd/paciente listar');

        $patientCares = PatientCare::query()
            ->tfd()
            ->where('is_archived', false)
            ->with(['patient.patientInfo', 'user.professional', 'escorts', 'reports.cid', 'reports.attachments'])
            ->latest('id')
            ->get();

        return response()->json($patientCares, JsonResponse::HTTP_OK);
    }

    /**
     * Listar pacientes/atendimentos arquivados do TFD.
     */
    public function getArchivePatients(): JsonResponse
    {
        $this->authorize('tfd/paciente listar');

        $patientCares = PatientCare::query()
            ->tfd()
            ->where('is_archived', true)
            ->with(['patient.patientInfo', 'user.professional', 'escorts', 'reports.cid', 'reports.attachments'])
            ->latest('id')
            ->get();

        return response()->json($patientCares, JsonResponse::HTTP_OK);
    }

    /**
     * Criar um novo paciente/atendimento.
     */
    public function createPatient(Request $request): JsonResponse
    {
        $this->authorize('tfd/paciente criar');

        return $this->patientService->createPatient($request);
    }

    /**
     * Atualizar dados completos de um paciente/atendimento.
     */
    public function updatePatient(PatientCare $patient_care, Request $request): JsonResponse
    {
        $this->authorize('tfd/paciente atualizar');

        return $this->patientService->updatePatient($patient_care, $request);
    }

    /**
     * Arquivar um atendimento de paciente.
     */
    public function archivePatient(PatientCare $patient_care): JsonResponse
    {
        $this->authorize('tfd/paciente atualizar');

        return $this->patientService->archivePatient($patient_care);
    }

    /**
     * Desalocar/remover um atendimento do arquivo.
     */
    public function movePatientFromArchive(PatientCare $patient_care): JsonResponse
    {
        $this->authorize('tfd/paciente atualizar');

        return $this->patientService->movePatientFromArchive($patient_care);
    }

    /**
     * Movimentar um atendimento do setor "Outros".
     */
    public function movePatientFromOthers(PatientCare $patient_care): JsonResponse
    {
        $this->authorize('tfd/paciente atualizar');

        return $this->patientService->movePatientFromOthers($patient_care);
    }

    /**
     * Validar status de um atendimento.
     */
    public function validatePatient(PatientCare $patient_care): JsonResponse
    {
        $this->authorize('tfd/paciente validar');

        return $this->patientService->validatePatient($patient_care);
    }

    /**
     * Finalizar o retorno de uma solicitação no contexto de pareceres.
     */
    public function finishBackPatient(PatientCare $patient_care): JsonResponse
    {
        $this->authorize('tfd/paciente atualizar');

        return $this->patientService->finishBackPatient($patient_care);
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Acompanhantes
    |--------------------------------------------------------------------------
    */

    /**
     * Listar acompanhantes vinculados ao atendimento.
     */
    public function getPatientEscorts(PatientCare $patient_care): JsonResponse
    {
        $this->authorize('tfd/paciente acompanhantes');

        $escorts = $patient_care->escorts()
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($escorts, JsonResponse::HTTP_OK);
    }

    /**
     * Cadastrar acompanhante para um atendimento.
     */
    public function createPatientEscort(PatientCare $patient_care, Request $request): JsonResponse
    {
        $this->authorize('tfd/paciente acompanhantes');

        return $this->patientService->createEscort($patient_care, $request);
    }

    /**
     * Atualizar dados do acompanhante.
     */
    public function updatePatientEscort(PatientCare $patient_care, Escort $escort, Request $request): JsonResponse
    {
        $this->authorize('tfd/paciente acompanhantes');

        return $this->patientService->updateEscort($escort, $request);
    }

    /**
     * Excluir vínculo de acompanhante com o atendimento.
     */
    public function deletePatientEscort(PatientCare $patient_care, PatientCareEscort $patient_care_escort): JsonResponse
    {
        $this->authorize('tfd/paciente acompanhantes');

        return $this->patientService->deleteEscort($patient_care_escort);
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Laudos e CIDs
    |--------------------------------------------------------------------------
    */

    /**
     * Listar laudos vinculados ao atendimento.
     */
    public function getPatientReports(PatientCare $patient_care): JsonResponse
    {
        $this->authorize('tfd/paciente laudos');

        $reports = $patient_care->reports()
            ->with(['patientCare', 'cid', 'attachments'])
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($reports, JsonResponse::HTTP_OK);
    }

    /**
     * Listar CIDs disponíveis para vínculo no atendimento.
     */
    public function getCids(PatientCare $patient_care): JsonResponse
    {
        $this->authorize('tfd/paciente laudos');

        $cids = Cid::query()
            ->whereNotIn('id', $patient_care->reports()->pluck('cid_id'))
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($cids, JsonResponse::HTTP_OK);
    }

    /**
     * Criar novo laudo para o atendimento.
     */
    public function createPatientReport(PatientCare $patient_care, Request $request): JsonResponse
    {
        $this->authorize('tfd/paciente laudos');

        return $this->patientService->createReport($patient_care, $request);
    }

    /**
     * Atualizar laudo existente.
     */
    public function updatePatientReport(PatientCare $patient_care, Report $report, Request $request): JsonResponse
    {
        $this->authorize('tfd/paciente laudos');

        return $this->patientService->updateReport($report, $request);
    }

    /**
     * Excluir laudo do atendimento.
     */
    public function deletePatientReport(PatientCare $patient_care, Report $report): JsonResponse
    {
        $this->authorize('tfd/paciente laudos');

        return $this->patientService->deleteReport($report);
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Anexos do Laudo
    |--------------------------------------------------------------------------
    */

    /**
     * Listar anexos de um laudo.
     */
    public function getReportAttachments(PatientCare $patient_care, Report $report): JsonResponse
    {
        $this->authorize('tfd/paciente laudos');

        $attachments = $report->attachments()
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($attachments, JsonResponse::HTTP_OK);
    }

    /**
     * Anexar arquivo a um laudo.
     */
    public function createReportAttachment(PatientCare $patient_care, Report $report, Request $request): JsonResponse
    {
        $this->authorize('tfd/paciente laudos');

        return $this->patientService->createReportAttachment($report, $request);
    }

    /**
     * Atualizar anexo do laudo.
     */
    public function updateReportAttachment(
        PatientCare $patient_care,
        Report $report,
        ReportAttachment $report_attachment,
        Request $request
    ): JsonResponse {
        $this->authorize('tfd/paciente laudos');

        return $this->patientService->updateReportAttachment($report_attachment, $request);
    }

    /**
     * Remover anexo do laudo.
     */
    public function deleteReportAttachment(
        PatientCare $patient_care,
        Report $report,
        ReportAttachment $report_attachment
    ): JsonResponse {
        $this->authorize('tfd/paciente laudos');

        return $this->patientService->deleteReportAttachment($report_attachment);
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas Diretas (Busca e Validação via Query Params)
    |--------------------------------------------------------------------------
    */

    /**
     * Buscar dados de paciente pelo CNS ou Documento (?cns=... ou ?document=...).
     */
    public function searchPatient(Request $request): JsonResponse
    {
        $this->authorize('tfd/paciente listar');

        if ($request->filled('cns')) {
            $cleanCns = preg_replace('/\D/', '', (string) $request->query('cns'));

            $patient = Patient::query()
                ->withExists(['patientCares as exists_in_tfd' => fn($q) => $q->tfd()])
                ->where('cns', $cleanCns)
                ->first();

            return response()->json($patient, JsonResponse::HTTP_OK);
        }

        if ($request->filled('document')) {
            $cleanDocument = preg_replace('/\D/', '', (string) $request->query('document'));

            $patient = Patient::query()
                ->withExists(['patientCares as exists_in_tfd' => fn($q) => $q->tfd()])
                ->where('document', $cleanDocument)
                ->first();

            return response()->json($patient, JsonResponse::HTTP_OK);
        }

        return response()->json([
            'message' => 'Informe o parâmetro cns ou document para busca.',
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Buscar dados de acompanhante pelo CNS ou Documento (?cns=... ou ?document=...).
     */
    public function searchEscort(Request $request): JsonResponse
    {
        $this->authorize('tfd/paciente acompanhantes');

        if ($request->filled('cns')) {
            $cleanCns = preg_replace('/\D/', '', (string) $request->query('cns'));

            $escort = Escort::query()
                ->where('cns', $cleanCns)
                ->first();

            return response()->json($escort, JsonResponse::HTTP_OK);
        }

        if ($request->filled('document')) {
            $cleanDocument = preg_replace('/\D/', '', (string) $request->query('document'));

            $escort = Escort::query()
                ->where('document', $cleanDocument)
                ->first();

            return response()->json($escort, JsonResponse::HTTP_OK);
        }

        return response()->json([
            'message' => 'Informe o parâmetro cns ou document para busca.',
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}
