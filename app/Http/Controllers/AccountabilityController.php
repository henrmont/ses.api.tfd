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
use Illuminate\Http\Request;

class AccountabilityController extends Controller
{
    use AuthorizesRequests;
    
    public function getPatientRequests()
    {
        $this->authorize('tfd/ajuda de custo listar');
        $patient_requests = PatientRequest::query()
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
            ->where('type','Agendamento')
            ->with('report.patientCare.patient','report.cid','report.attachments','attachments','hospitalUnity','medicalProfessional','ownerProfessional','socialProfessional','travelProfessional','costAssistanceProfessional','accountabilityProfessional','paymentProfessional','travels.passengers.patient','travels.passengers.escort','costAssistances.costAssistanceDailies.dailyCost', 'accountabilities.accountabilityDailies.dailyCost', 'paymentInfo','paymentAttachments')
            ->orderBy('id','desc')
            ->get();
        return response()->json($patient_requests, 200);
    }

    public function getArchivePatientRequests()
    {
        $this->authorize('tfd/ajuda de custo listar');
        $patient_requests = PatientRequest::query()
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
            ->with('report.patientCare.patient','report.patientCare.user.professional','report.cid','report.attachments','attachments','hospitalUnity','medicalProfessional','ownerProfessional','socialProfessional','travelProfessional','costAssistanceProfessional','accountabilityProfessional','paymentProfessional','travels.passengers.patient','travels.passengers.escort','costAssistances.costAssistanceDailies.dailyCost', 'accountabilities.accountabilityDailies.dailyCost', 'paymentInfo','paymentAttachments')
            ->orderBy('id','desc')
            ->get();
        return response()->json($patient_requests, 200);
    }

    public function haltedPatientRequest(PatientRequest $patient_request, AccountabilityService $accountabilityService)
    {
        $this->authorize('tfd/ajuda de custo atualizar');
        return $accountabilityService->haltedPatientRequest($patient_request);
    }

    public function getAccountabilities(PatientRequest $patient_request)
    {
        $this->authorize('tfd/ajuda de custo listar');
        $accountabilities = $patient_request->accountabilities()
            ->with('accountabilityDailies.dailyCost')
            ->orderBy('id','asc')
            ->get();
        return response()->json($accountabilities, 200);
    }

    public function getBalance(PatientCare $patient_care, PatientService $patientService)
    {
        $this->authorize('tfd/ajuda de custo listar');
        return $patientService->calcBalance($patient_care);
    }

    public function createAccountability(PatientRequest $patient_request, Request $request, AccountabilityService $accountabilityService)
    {
        $this->authorize('tfd/ajuda de custo criar');
        return $accountabilityService->createAccountability($patient_request, $request);
    }

    public function updateAccountability(Accountability $accountability, Request $request, AccountabilityService $accountabilityService)
    {
        $this->authorize('tfd/ajuda de custo atualizar');
        return $accountabilityService->updateAccountability($accountability, $request);
    }

    public function deleteAccountability(Accountability $accountability, AccountabilityService $accountabilityService)
    {
        $this->authorize('tfd/ajuda de custo deletar');
        return $accountabilityService->deleteAccountability($accountability);
    }

    public function getAccountabilityDailies(Accountability $accountability)
    {
        $this->authorize('tfd/ajuda de custo listar');
        $accountability_dailies = $accountability->accountabilityDailies()
            ->with('dailyCost')
            ->orderBy('id','asc')
            ->get();
        return response()->json($accountability_dailies, 200);
    }

    public function createAccountabilityDaily(Accountability $accountability, Request $request, AccountabilityService $accountabilityService)
    {
        $this->authorize('tfd/ajuda de custo criar');
        return $accountabilityService->createAccountabilityDaily($accountability, $request);
    }

    public function updateAccountabilityDaily(AccountabilityDaily $accountability_daily, Request $request, AccountabilityService $accountabilityService)
    {
        $this->authorize('tfd/ajuda de custo atualizar');
        return $accountabilityService->updateAccountabilityDaily($accountability_daily, $request);
    }

    public function deleteAccountabilityDaily(AccountabilityDaily $accountability_daily, Request $request, AccountabilityService $accountabilityService)
    {
        $this->authorize('tfd/ajuda de custo deletar');
        return $accountabilityService->deleteAccountabilityDaily($accountability_daily, $request);
    }

    public function archivePatientRequest(PatientRequest $patient_request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/ajuda de custo atualizar');
        return $patientRequestService->archiveAccountabilityPatientRequest($patient_request);
    }

    public function movePatientRequestFromArchive(PatientRequest $patient_request, AccountabilityService $accountabilityService)
    {
        $this->authorize('tfd/ajuda de custo atualizar');
        return $accountabilityService->movePatientRequestFromArchive($patient_request);
    }
    
}
