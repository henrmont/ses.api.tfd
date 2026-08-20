<?php

namespace App\Http\Controllers;

use App\Models\Accountability;
use App\Models\AccountabilityDaily;
use App\Models\DailyCost;
use App\Models\PatientCare;
use App\Models\PatientRequest;
use App\Services\AccountabilityService;
use App\Services\PatientRequestService;
use App\Services\PatientService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountabilityController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected AccountabilityService $accountabilityService,
        protected PatientService $patientService,
        protected PatientRequestService $patientRequestService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Gestão de Solicitações do TFD (Prestação de Contas)
    |--------------------------------------------------------------------------
    */

    /**
     * Listar solicitações de prestação de contas pendentes.
     */
    public function getPatientRequests(): JsonResponse
    {
        $this->authorize('tfd/ajuda de custo listar');

        $patientRequests = PatientRequest::query()
            ->notPatientBack()
            ->whereNotNull('accountability_professional_id')
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
     * Listar solicitações arquivadas na prestação de contas.
     */
    public function getArchivePatientRequests(): JsonResponse
    {
        $this->authorize('tfd/ajuda de custo listar');

        $patientRequests = PatientRequest::query()
            ->notPatientBack()
            ->where(function ($query) {
                $query->whereNull('back_to_owner')->orWhereNull('back_from_travel');
            })
            ->where(function ($query) {
                $query->whereNull('back_to_medical')->orWhereNull('back_from_travel');
            })
            ->where(function ($query) {
                $query->whereNull('back_to_social')->orWhereNull('back_from_travel');
            })
            ->where('is_cost_assistance_archived', true)
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
     * Sobrestar/paralisar a solicitação na prestação de contas.
     */
    public function haltedPatientRequest(PatientRequest $patient_request)
    {
        $this->authorize('tfd/ajuda de custo atualizar');

        return $this->accountabilityService->haltedPatientRequest($patient_request);
    }

    /**
     * Mover solicitação a partir do setor "Outros".
     */
    public function movePatientRequestFromOthers(PatientRequest $patient_request)
    {
        $this->authorize('tfd/ajuda de custo atualizar');

        return $this->accountabilityService->movePatientRequestFromOthers($patient_request);
    }

    /**
     * Arquivar a solicitação de prestação de contas.
     */
    public function archivePatientRequest(PatientRequest $patient_request)
    {
        $this->authorize('tfd/ajuda de custo atualizar');

        return $this->patientRequestService->archiveAccountabilityPatientRequest($patient_request);
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Prestações de Contas (Accountabilities)
    |--------------------------------------------------------------------------
    */

    /**
     * Listar prestações de contas vinculadas a uma solicitação.
     */
    public function getAccountabilities(PatientRequest $patient_request): JsonResponse
    {
        $this->authorize('tfd/ajuda de custo listar');

        $accountabilities = $patient_request->accountabilities()
            ->with('accountabilityDailies.dailyCost')
            ->oldest('id')
            ->get();

        return response()->json($accountabilities, JsonResponse::HTTP_OK);
    }

    /**
     * Criar registro de prestação de contas.
     */
    public function createAccountability(PatientRequest $patient_request, Request $request)
    {
        $this->authorize('tfd/ajuda de custo criar');

        return $this->accountabilityService->createAccountability($patient_request, $request);
    }

    /**
     * Atualizar registro de prestação de contas.
     */
    public function updateAccountability(Accountability $accountability, Request $request)
    {
        $this->authorize('tfd/ajuda de custo atualizar');

        return $this->accountabilityService->updateAccountability($accountability, $request);
    }

    /**
     * Excluir registro de prestação de contas.
     */
    public function deleteAccountability(Accountability $accountability)
    {
        $this->authorize('tfd/ajuda de custo deletar');

        return $this->accountabilityService->deleteAccountability($accountability);
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Diárias da Prestação de Contas (AccountabilityDailies)
    |--------------------------------------------------------------------------
    */

    /**
     * Listar diárias vinculadas a uma prestação de contas.
     */
    public function getAccountabilityDailies(Accountability $accountability): JsonResponse
    {
        $this->authorize('tfd/ajuda de custo listar');

        $accountabilityDailies = $accountability->accountabilityDailies()
            ->with('dailyCost')
            ->oldest('id')
            ->get();

        return response()->json($accountabilityDailies, JsonResponse::HTTP_OK);
    }

    /**
     * Cadastrar diária na prestação de contas.
     */
    public function createAccountabilityDaily(Accountability $accountability, Request $request)
    {
        $this->authorize('tfd/ajuda de custo criar');

        return $this->accountabilityService->createAccountabilityDaily($accountability, $request);
    }

    /**
     * Atualizar diária da prestação de contas.
     */
    public function updateAccountabilityDaily(AccountabilityDaily $accountability_daily, Request $request)
    {
        $this->authorize('tfd/ajuda de custo atualizar');

        return $this->accountabilityService->updateAccountabilityDaily($accountability_daily, $request);
    }

    /**
     * Excluir diária da prestação de contas.
     */
    public function deleteAccountabilityDaily(AccountabilityDaily $accountability_daily, Request $request)
    {
        $this->authorize('tfd/ajuda de custo deletar');

        return $this->accountabilityService->deleteAccountabilityDaily($accountability_daily, $request);
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas Auxiliares
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
}