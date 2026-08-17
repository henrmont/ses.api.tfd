<?php

namespace App\Http\Controllers;

use App\Models\HospitalUnity;
use App\Models\PatientCare;
use App\Models\PatientRequest;
use App\Models\PatientRequestAttachment;
use App\Models\Professional;
use App\Services\PatientRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected PatientRequestService $patientRequestService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Gestão de Solicitações (TFD)
    |--------------------------------------------------------------------------
    */

    /**
     * Listar solicitações ativas do TFD.
     */
    public function getPatientRequests(): JsonResponse
    {
        $this->authorize('tfd/solicitação listar');

        $userProfessionalId = Professional::where('user_id', auth()->id())->value('id');

        $patientRequests = PatientRequest::query()
            ->notPatientBack()
            ->where(function ($query) use ($userProfessionalId) {
                $query->whereNull('back_to_owner')
                    ->orWhere('back_from_cost_assistance', $userProfessionalId)
                    ->orWhere('back_from_travel', $userProfessionalId);
            })
            ->where('is_opinion_archived', false)
            ->with([
                'report.patientCare.patient',
                'report.patientCare.user.professional',
                'report.cid',
                'report.attachments',
                'attachments',
                'hospitalUnity',
                'medicalProfessional',
                'ownerProfessional',
                'socialProfessional',
                'travelProfessional',
                'costAssistanceProfessional',
                'accountabilityProfessional',
                'travels.passengers.patient',
                'travels.passengers.escort',
                'costAssistances.costAssistanceDailies.dailyCost',
                'accountabilities.accountabilityDailies.dailyCost'
            ])
            ->latest('id')
            ->get();

        return response()->json($patientRequests, JsonResponse::HTTP_OK);
    }

    /**
     * Criar uma nova solicitação.
     */
    public function createPatientRequest(Request $request)
    {
        $this->authorize('tfd/solicitação criar');

        return $this->patientRequestService->createPatientRequest($request);
    }

    /**
     * Atualizar dados de uma solicitação.
     */
    public function updatePatientRequest(PatientRequest $patient_request, Request $request)
    {
        $this->authorize('tfd/solicitação atualizar');

        return $this->patientRequestService->updatePatientRequest($patient_request, $request);
    }

    /**
     * Excluir uma solicitação.
     */
    public function deletePatientRequest(PatientRequest $patient_request)
    {
        $this->authorize('tfd/solicitação deletar');

        return $this->patientRequestService->deletePatientRequest($patient_request);
    }

    /**
     * Sobrestar/paralisar uma solicitação.
     */
    public function haltedPatientRequest(PatientRequest $patient_request)
    {
        $this->authorize('tfd/solicitação atualizar');

        return $this->patientRequestService->haltedPatientRequest($patient_request);
    }

    /**
     * Encaminhar/processar solicitação para o regulador médico.
     */
    public function processPatientRequestToMedical(PatientRequest $patient_request, Request $request)
    {
        $this->authorize('tfd/solicitação atualizar');

        return $this->patientRequestService->processPatientRequestToMedical($patient_request, $request);
    }

    /**
     * Movimentar solicitação a partir da aba de processos.
     */
    public function movePatientRequestFromProcesses(PatientRequest $patient_request)
    {
        $this->authorize('tfd/solicitação atualizar');

        return $this->patientRequestService->movePatientRequestFromProcesses($patient_request);
    }

    /**
     * Movimentar solicitação a partir do setor "Outros".
     */
    public function movePatientRequestFromOthers(PatientRequest $patient_request)
    {
        $this->authorize('tfd/solicitação atualizar');

        return $this->patientRequestService->movePatientRequestFromOthers($patient_request);
    }

    /**
     * Finalizar o retorno de uma solicitação.
     */
    public function finishBackPatientRequest(PatientRequest $patient_request)
    {
        $this->authorize('tfd/solicitação atualizar');

        return $this->patientRequestService->finishBackPatientRequest($patient_request);
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Anexos da Solicitação
    |--------------------------------------------------------------------------
    */

    /**
     * Listar anexos de uma solicitação.
     */
    public function getPatientRequestAttachments(PatientRequest $patient_request): JsonResponse
    {
        $this->authorize('tfd/solicitação anexos');

        $attachments = $patient_request->attachments()
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($attachments, JsonResponse::HTTP_OK);
    }

    /**
     * Anexar arquivo a uma solicitação.
     */
    public function createPatientRequestAttachment(PatientRequest $patient_request, Request $request)
    {
        $this->authorize('tfd/solicitação anexos');

        return $this->patientRequestService->createPatientRequestAttachment($patient_request, $request);
    }

    /**
     * Atualizar anexo da solicitação.
     */
    public function updatePatientRequestAttachment(PatientRequestAttachment $patient_request_attachment, Request $request)
    {
        $this->authorize('tfd/solicitação anexos');

        return $this->patientRequestService->updatePatientRequestAttachment($patient_request_attachment, $request);
    }

    /**
     * Remover anexo da solicitação.
     */
    public function deletePatientRequestAttachment(PatientRequestAttachment $patient_request_attachment)
    {
        $this->authorize('tfd/solicitação anexos');

        return $this->patientRequestService->deletePatientRequestAttachment($patient_request_attachment);
    }

    /*
    |--------------------------------------------------------------------------
    | Auxiliares de Consulta para Formulação de Solicitações
    |--------------------------------------------------------------------------
    */

    /**
     * Listar atendimentos ativos elegíveis no TFD.
     */
    public function getPatients(): JsonResponse
    {
        $this->authorize('tfd/solicitação listar');

        $patientCares = PatientCare::query()
            ->tfd()
            ->with('patient')
            ->latest('id')
            ->get();

        return response()->json($patientCares, JsonResponse::HTTP_OK);
    }

    /**
     * Listar laudos vinculados a um atendimento específico.
     */
    public function getPatientReports(PatientCare $patient_care): JsonResponse
    {
        $this->authorize('tfd/solicitação listar');

        $reports = $patient_care->reports()
            ->with('cid')
            ->latest('id')
            ->get();

        return response()->json($reports, JsonResponse::HTTP_OK);
    }

    /**
     * Listar unidades hospitalares.
     */
    public function getHospitalUnities(): JsonResponse
    {
        $this->authorize('tfd/solicitação listar');

        $hospitalUnities = HospitalUnity::query()
            ->latest('id')
            ->get();

        return response()->json($hospitalUnities, JsonResponse::HTTP_OK);
    }

    /**
     * Listar profissionais médicos e totalizador de solicitações reguladas.
     */
    public function getMedicalProfessionals(): JsonResponse
    {
        $this->authorize('tfd/solicitação listar');

        $medicalProfessionals = Professional::query()
            ->with('user')
            ->withCount('patientMedicalRequests')
            ->where('type', 'Médico')
            ->get();

        return response()->json($medicalProfessionals, JsonResponse::HTTP_OK);
    }
}