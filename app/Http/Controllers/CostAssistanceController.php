<?php

namespace App\Http\Controllers;

use App\Models\CostAssistance;
use App\Models\CostAssistanceDaily;
use App\Models\DailyCost;
use App\Models\PatientCare;
use App\Models\PatientRequest;
use App\Models\Professional;
use App\Models\Report;
use App\Services\CostAssistanceService;
use App\Services\PatientRequestService;
use App\Services\PatientService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CostAssistanceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected CostAssistanceService $costAssistanceService,
        protected PatientService $patientService,
        protected PatientRequestService $patientRequestService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Gestão de Solicitações do TFD (Ajudas de Custo)
    |--------------------------------------------------------------------------
    */

    /**
     * Listar solicitações de ajuda de custo pendentes.
     */
    public function getPatientRequests(): JsonResponse
    {
        $this->authorize('tfd/ajuda de custo listar');

        $patientRequests = PatientRequest::query()
            ->notPatientBack()
            ->whereNull('back_to_travel')
            ->where(function ($query) {
                $query->whereNull('back_to_owner')->orWhereNull('back_from_cost_assistance');
            })
            ->where(function ($query) {
                $query->whereNull('back_to_medical')->orWhereNull('back_from_cost_assistance');
            })
            ->where(function ($query) {
                $query->whereNull('back_to_social')->orWhereNull('back_from_cost_assistance');
            })
            ->where('is_cost_assistance_archived', false)
            ->where('type', 'Agendamento')
            ->with([
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
            ->latest('id')
            ->get();

        return response()->json($patientRequests, JsonResponse::HTTP_OK);
    }

    /**
     * Listar solicitações arquivadas no setor de ajudas de custo.
     */
    public function getArchivePatientRequests(): JsonResponse
    {
        $this->authorize('tfd/ajuda de custo listar');

        $patientRequests = PatientRequest::query()
            ->notPatientBack()
            ->whereNull('back_to_travel')
            ->where(function ($query) {
                $query->whereNull('back_to_owner')->orWhereNull('back_from_cost_assistance');
            })
            ->where(function ($query) {
                $query->whereNull('back_to_medical')->orWhereNull('back_from_cost_assistance');
            })
            ->where(function ($query) {
                $query->whereNull('back_to_social')->orWhereNull('back_from_cost_assistance');
            })
            ->where('is_cost_assistance_archived', true)
            ->where('type', 'Agendamento')
            ->with([
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
            ->latest('id')
            ->get();

        return response()->json($patientRequests, JsonResponse::HTTP_OK);
    }

    /**
     * Listar histórico de solicitações do mesmo relatório.
     */
    public function getHistoryPatientRequests(Report $report, PatientRequest $patient_request): JsonResponse
    {
        $this->authorize('tfd/ajuda de custo listar');

        $patientRequests = $report->patientRequests()
            ->whereNot('id', $patient_request->id)
            ->where('type', 'Agendamento')
            ->with([
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
                // 'paymentInfo',
                // 'paymentAttachments',
            ])
            ->latest('id')
            ->get();

        return response()->json($patientRequests, JsonResponse::HTTP_OK);
    }

    /**
     * Obter saldo calculado do atendimento do paciente.
     */
    public function getBalance(PatientCare $patient_care)
    {
        $this->authorize('tfd/ajuda de custo listar');

        return $this->patientService->calcBalance($patient_care);
    }

    /*
    |--------------------------------------------------------------------------
    | Tramitação e Mudança de Estados da Solicitação
    |--------------------------------------------------------------------------
    */

    /**
     * Sobrestar/paralisar a solicitação de ajuda de custo.
     */
    public function haltedPatientRequest(PatientRequest $patient_request)
    {
        $this->authorize('tfd/ajuda de custo atualizar');

        return $this->costAssistanceService->haltedPatientRequest($patient_request);
    }

    /**
     * Desfazer ação / devolver solicitação no fluxo de ajudas de custo.
     */
    public function undoPatientRequest(PatientRequest $patient_request, Request $request)
    {
        $this->authorize('tfd/ajuda de custo atualizar');

        return $this->patientRequestService->undoPatientRequest($patient_request, $request, 'cost assistance');
    }

    /**
     * Mover solicitação a partir do histórico.
     */
    public function movePatientRequestFromHistory(PatientRequest $patient_request)
    {
        $this->authorize('tfd/ajuda de custo atualizar');

        return $this->costAssistanceService->movePatientRequestFromHistory($patient_request);
    }

    /**
     * Mover solicitação a partir do setor "Outros".
     */
    public function movePatientRequestFromOthers(PatientRequest $patient_request)
    {
        $this->authorize('tfd/ajuda de custo atualizar');

        return $this->costAssistanceService->movePatientRequestFromOthers($patient_request);
    }

    /**
     * Mover solicitação a partir do arquivo.
     */
    public function movePatientRequestFromArchive(PatientRequest $patient_request)
    {
        $this->authorize('tfd/ajuda de custo atualizar');

        return $this->costAssistanceService->movePatientRequestFromArchive($patient_request);
    }

    /**
     * Arquivar a solicitação de ajuda de custo.
     */
    public function archivePatientRequest(PatientRequest $patient_request)
    {
        $this->authorize('tfd/ajuda de custo atualizar');

        return $this->costAssistanceService->archivePatientRequest($patient_request);
    }

    /**
     * Finalizar devolução da solicitação de ajuda de custo.
     */
    public function finishBackPatientRequest(PatientRequest $patient_request)
    {
        $this->authorize('tfd/ajuda de custo atualizar');

        return $this->costAssistanceService->finishBackPatientRequest($patient_request);
    }

    /**
     * Tramitar solicitação para o setor de pagamento.
     */
    public function processPatientRequestToPayment(PatientRequest $patient_request, Request $request)
    {
        $this->authorize('tfd/ajuda de custo atualizar');

        return $this->costAssistanceService->processPatientRequestToPayment($patient_request, $request);
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Ajudas de Custo (CostAssistances)
    |--------------------------------------------------------------------------
    */

    /**
     * Listar ajudas de custo vinculadas a uma solicitação.
     */
    public function getCostAssistances(PatientRequest $patient_request): JsonResponse
    {
        $this->authorize('tfd/ajuda de custo listar');

        $costAssistances = $patient_request->costAssistances()
            ->with([
                'costAssistanceDailies.dailyCost',
                'patientRequest.travels.passengers.escort',
                'patientRequest.travels.passengers.patient',
                'passenger.patient',
                'passenger.escort',
            ])
            ->oldest('id')
            ->get();

        return response()->json($costAssistances, JsonResponse::HTTP_OK);
    }

    /**
     * Criar ajuda de custo.
     */
    public function createCostAssistance(PatientRequest $patient_request, Request $request)
    {
        $this->authorize('tfd/ajuda de custo criar');

        return $this->costAssistanceService->createCostAssistance($patient_request, $request);
    }

    /**
     * Atualizar ajuda de custo.
     */
    public function updateCostAssistance(CostAssistance $cost_assistance, Request $request)
    {
        $this->authorize('tfd/ajuda de custo atualizar');

        return $this->costAssistanceService->updateCostAssistance($cost_assistance, $request);
    }

    /**
     * Excluir ajuda de custo.
     */
    public function deleteCostAssistance(CostAssistance $cost_assistance)
    {
        $this->authorize('tfd/ajuda de custo deletar');

        return $this->costAssistanceService->deleteCostAssistance($cost_assistance);
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Diárias das Ajudas de Custo (CostAssistanceDailies)
    |--------------------------------------------------------------------------
    */

    /**
     * Listar diárias vinculadas a uma ajuda de custo.
     */
    public function getCostAssistanceDailies(CostAssistance $cost_assistance): JsonResponse
    {
        $this->authorize('tfd/ajuda de custo listar');

        $costAssistanceDailies = $cost_assistance->costAssistanceDailies()
            ->with('dailyCost')
            ->oldest('id')
            ->get();

        return response()->json($costAssistanceDailies, JsonResponse::HTTP_OK);
    }

    /**
     * Cadastrar diária na ajuda de custo.
     */
    public function createCostAssistanceDaily(CostAssistance $cost_assistance, Request $request)
    {
        $this->authorize('tfd/ajuda de custo criar');

        return $this->costAssistanceService->createCostAssistanceDaily($cost_assistance, $request);
    }

    /**
     * Atualizar diária da ajuda de custo.
     */
    public function updateCostAssistanceDaily(CostAssistanceDaily $cost_assistance_daily, Request $request)
    {
        $this->authorize('tfd/ajuda de custo atualizar');

        return $this->costAssistanceService->updateCostAssistanceDaily($cost_assistance_daily, $request);
    }

    /**
     * Excluir diária da ajuda de custo.
     */
    public function deleteCostAssistanceDaily(CostAssistanceDaily $cost_assistance_daily)
    {
        $this->authorize('tfd/ajuda de custo deletar');

        return $this->costAssistanceService->deleteCostAssistanceDaily($cost_assistance_daily);
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas Auxiliares e Profissionais
    |--------------------------------------------------------------------------
    */

    /**
     * Listar valores/tipos de diárias cadastrados.
     */
    public function getDailyCosts(): JsonResponse
    {
        $this->authorize('tfd/ajuda de custo listar');

        $dailyCosts = DailyCost::query()
            ->oldest('id')
            ->get();

        return response()->json($dailyCosts, JsonResponse::HTTP_OK);
    }

    /**
     * Listar profissionais do setor de pagamento.
     */
    public function getPaymentProfessionals(): JsonResponse
    {
        $this->authorize('tfd/ajuda de custo listar');

        $paymentProfessionals = Professional::query()
            ->with('user')
            ->withCount('patientPaymentRequests')
            ->where('type', 'Pagamento')
            ->get();

        return response()->json($paymentProfessionals, JsonResponse::HTTP_OK);
    }
}