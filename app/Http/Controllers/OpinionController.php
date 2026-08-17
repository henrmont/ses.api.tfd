<?php

namespace App\Http\Controllers;

use App\Models\Opinion;
use App\Models\PatientRequest;
use App\Models\Professional;
use App\Models\Report;
use App\Services\OpinionService;
use App\Services\PatientRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpinionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected OpinionService $opinionService,
        protected PatientRequestService $patientRequestService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Gestão de Pareceres e Solicitações do TFD
    |--------------------------------------------------------------------------
    */

    /**
     * Listar solicitações pendentes de parecer/tramitação.
     */
    public function getPatientRequests(): JsonResponse
    {
        $this->authorize('tfd/parecer listar');

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
                'accountabilities.accountabilityDailies.dailyCost',
            ])
            ->latest('id')
            ->get();

        return response()->json($patientRequests, JsonResponse::HTTP_OK);
    }

    /**
     * Listar solicitações arquivadas na aba de pareceres.
     */
    public function getArchivePatientRequests(): JsonResponse
    {
        $this->authorize('tfd/parecer listar');

        $patientRequests = PatientRequest::query()
            ->notPatientBack()
            ->whereNull('back_to_owner')
            ->where('is_opinion_archived', true)
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
                'accountabilities.accountabilityDailies.dailyCost',
            ])
            ->latest('id')
            ->get();

        return response()->json($patientRequests, JsonResponse::HTTP_OK);
    }

    /**
     * Retornar o tipo/perfil do profissional autenticado.
     */
    public function getType(): JsonResponse
    {
        $this->authorize('tfd/parecer listar');

        $type = Professional::where('user_id', auth()->id())->value('type');

        return response()->json($type, JsonResponse::HTTP_OK);
    }

    /**
     * Listar pareceres emitidos de uma solicitação específica.
     */
    public function getOpinions(PatientRequest $patient_request): JsonResponse
    {
        $this->authorize('tfd/parecer listar');

        $opinions = $patient_request->opinions()
            ->with('patientRequest.report.patientCare', 'professional')
            ->oldest('id')
            ->get();

        return response()->json($opinions, JsonResponse::HTTP_OK);
    }

    /**
     * Criar parecer técnico em uma solicitação.
     */
    public function createOpinion(PatientRequest $patient_request, Request $request)
    {
        $this->authorize('tfd/parecer criar');

        return $this->opinionService->createOpinion($patient_request, $request);
    }

    /**
     * Atualizar parecer técnico existente.
     */
    public function updateOpinion(Opinion $opinion, Request $request)
    {
        $this->authorize('tfd/parecer atualizar');

        return $this->opinionService->updateOpinion($opinion, $request);
    }

    /**
     * Excluir parecer técnico.
     */
    public function deleteOpinion(Opinion $opinion)
    {
        $this->authorize('tfd/parecer deletar');

        return $this->opinionService->deleteOpinion($opinion);
    }

    /**
     * Consultar o histórico das solicitações vinculadas a um laudo.
     */
    public function getHistoryPatientRequests(Report $report, PatientRequest $patient_request): JsonResponse
    {
        $this->authorize('tfd/parecer listar');

        $patientRequests = $report->patientRequests()
            ->whereNot('id', $patient_request->id)
            ->with([
                'opinions',
                'report.patientCare.patient',
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
                'accountabilities.accountabilityDailies.dailyCost',
            ])
            ->oldest('id')
            ->get();

        return response()->json($patientRequests, JsonResponse::HTTP_OK);
    }

    /*
    |--------------------------------------------------------------------------
    | Encaminhamentos e Tramitações de Processo
    |--------------------------------------------------------------------------
    */

    /**
     * Encaminhar solicitação para o Serviço Social (Assistente Social).
     */
    public function processPatientRequestToSocial(PatientRequest $patient_request, Request $request)
    {
        $this->authorize('tfd/parecer atualizar');

        return $this->opinionService->processPatientRequestToSocial($patient_request, $request);
    }

    /**
     * Encaminhar solicitação para Ajuda de Custo e/ou Passagem.
     */
    public function processPatientRequestToCostAssistanceAndTravel(PatientRequest $patient_request, Request $request)
    {
        $this->authorize('tfd/parecer atualizar');

        return $this->opinionService->processPatientRequestToCostAssistanceAndTravel($patient_request, $request);
    }

    /**
     * Devolver/restaurar fluxo de solicitação.
     */
    public function undoPatientRequest(PatientRequest $patient_request, Request $request)
    {
        $this->authorize('tfd/parecer atualizar');

        return $this->patientRequestService->undoPatientRequest($patient_request, $request);
    }

    /**
     * Finalizar a devolução da solicitação.
     */
    public function finishBackPatientRequest(PatientRequest $patient_request, string $type)
    {
        $this->authorize('tfd/parecer atualizar');

        return $this->opinionService->finishBackPatientRequest($patient_request, $type);
    }

    /*
    |--------------------------------------------------------------------------
    | Movimentações e Arquivamento
    |--------------------------------------------------------------------------
    */

    /**
     * Arquivar solicitação no módulo de pareceres.
     */
    public function archivePatientRequest(PatientRequest $patient_request)
    {
        $this->authorize('tfd/parecer atualizar');

        return $this->opinionService->archivePatientRequest($patient_request);
    }

    /**
     * Sobrestar/paralisar a solicitação no fluxo do parecer.
     */
    public function haltedPatientRequest(PatientRequest $patient_request, string $type)
    {
        $this->authorize('tfd/parecer atualizar');

        return $this->opinionService->haltedPatientRequest($patient_request, $type);
    }

    /**
     * Mover solicitação a partir da aba de processos.
     */
    public function movePatientRequestFromProcesses(PatientRequest $patient_request, string $type)
    {
        $this->authorize('tfd/parecer atualizar');

        return $this->opinionService->movePatientRequestFromProcesses($patient_request, $type);
    }

    /**
     * Mover solicitação a partir do arquivo.
     */
    public function movePatientRequestFromArchive(PatientRequest $patient_request, string $type)
    {
        $this->authorize('tfd/parecer atualizar');

        return $this->opinionService->movePatientRequestFromArchive($patient_request, $type);
    }

    /**
     * Mover solicitação a partir do setor "Outros".
     */
    public function movePatientRequestFromOthers(PatientRequest $patient_request, string $type)
    {
        $this->authorize('tfd/parecer atualizar');

        return $this->opinionService->movePatientRequestFromOthers($patient_request, $type);
    }

    /*
    |--------------------------------------------------------------------------
    | Auxiliares de Consulta para Profissionais
    |--------------------------------------------------------------------------
    */

    /**
     * Listar profissionais da Assistência Social.
     */
    public function getSocialProfessionals(): JsonResponse
    {
        $this->authorize('tfd/parecer listar');

        $socialProfessionals = Professional::query()
            ->with('user')
            ->withCount('patientSocialRequests')
            ->where('type', 'Assistente Social')
            ->get();

        return response()->json($socialProfessionals, JsonResponse::HTTP_OK);
    }

    /**
     * Listar profissionais do setor de Ajuda de Custo.
     */
    public function getCostAssistanceProfessionals(): JsonResponse
    {
        $this->authorize('tfd/parecer listar');

        $costAssistanceProfessionals = Professional::query()
            ->with('user')
            ->withCount('patientCostAssistanceRequests')
            ->where('type', 'Ajuda de Custo')
            ->get();

        return response()->json($costAssistanceProfessionals, JsonResponse::HTTP_OK);
    }

    /**
     * Listar profissionais do setor de Passagem/Viagem.
     */
    public function getTravelProfessionals(): JsonResponse
    {
        $this->authorize('tfd/parecer listar');

        $travelProfessionals = Professional::query()
            ->with('user')
            ->withCount('patientTravelRequests')
            ->where('type', 'Passagem')
            ->get();

        return response()->json($travelProfessionals, JsonResponse::HTTP_OK);
    }
}