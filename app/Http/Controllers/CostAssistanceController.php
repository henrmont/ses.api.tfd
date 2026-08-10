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
use Illuminate\Http\Request;

class CostAssistanceController extends Controller
{
    use AuthorizesRequests;
    
    public function getPatientRequests()
    {
        $this->authorize('tfd/ajuda de custo listar');
        $patient_requests = PatientRequest::query()
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
            ->where('type','Agendamento')
            ->with('report.patientCare.patient','report.cid','report.attachments','attachments','hospitalUnity','medicalProfessional','ownerProfessional','socialProfessional','travelProfessional','costAssistanceProfessional','accountabilityProfessional','travels.passengers.patient','travels.passengers.escort','costAssistances.costAssistanceDailies.dailyCost', 'accountabilities.accountabilityDailies.dailyCost')
            ->orderBy('id','desc')
            ->get();
        return response()->json($patient_requests, 200);
    }

    public function haltedPatientRequest(PatientRequest $patient_request, CostAssistanceService $costAssistanceService)
    {
        $this->authorize('tfd/ajuda de custo atualizar');
        return $costAssistanceService->haltedPatientRequest($patient_request);
    }

    public function getCostAssistances(PatientRequest $patient_request)
    {
        $this->authorize('tfd/ajuda de custo listar');
        $cost_assistances = $patient_request->costAssistances()
            ->with('costAssistanceDailies.dailyCost','patientRequest.travels.passengers.escort','patientRequest.travels.passengers.patient','passenger.patient','passenger.escort')
            ->orderBy('id','asc')
            ->get();
        return response()->json($cost_assistances, 200);
    }

    public function getBalance(PatientCare $patient_care, PatientService $patientService)
    {
        $this->authorize('tfd/ajuda de custo listar');
        return $patientService->calcBalance($patient_care);
    }

    public function createCostAssistance(PatientRequest $patient_request, Request $request, CostAssistanceService $costAssistanceService)
    {
        $this->authorize('tfd/ajuda de custo criar');
        return $costAssistanceService->createCostAssistance($patient_request, $request);
    }

    public function updateCostAssistance(CostAssistance $cost_assistance, Request $request, CostAssistanceService $costAssistanceService)
    {
        $this->authorize('tfd/ajuda de custo atualizar');
        return $costAssistanceService->updateCostAssistance($cost_assistance, $request);
    }

    public function deleteCostAssistance(CostAssistance $cost_assistance, CostAssistanceService $costAssistanceService)
    {
        $this->authorize('tfd/ajuda de custo deletar');
        return $costAssistanceService->deleteCostAssistance($cost_assistance);
    }

    public function getCostAssistanceDailies(CostAssistance $cost_assistance)
    {
        $this->authorize('tfd/ajuda de custo listar');
        $cost_assistance_dailies = $cost_assistance->costAssistanceDailies()
            ->with('dailyCost')
            ->orderBy('id','asc')
            ->get();
        return response()->json($cost_assistance_dailies, 200);
    }

    public function getDailyCosts()
    {
        $this->authorize('tfd/ajuda de custo listar');
        $daily_costs = DailyCost::query()
            ->orderBy('id', 'asc')
            ->get();
        return response()->json($daily_costs, 200);
    }

    public function createCostAssistanceDaily(CostAssistance $cost_assistance, Request $request, CostAssistanceService $costAssistanceService)
    {
        $this->authorize('tfd/ajuda de custo criar');
        return $costAssistanceService->createCostAssistanceDaily($cost_assistance, $request);
    }

    public function updateCostAssistanceDaily(CostAssistanceDaily $cost_assistance_daily, Request $request, CostAssistanceService $costAssistanceService)
    {
        $this->authorize('tfd/ajuda de custo atualizar');
        return $costAssistanceService->updateCostAssistanceDaily($cost_assistance_daily, $request);
    }

    public function deleteCostAssistanceDaily(CostAssistanceDaily $cost_assistance_daily, CostAssistanceService $costAssistanceService)
    {
        $this->authorize('tfd/ajuda de custo deletar');
        return $costAssistanceService->deleteCostAssistanceDaily($cost_assistance_daily);
    }

    public function getHistoryPatientRequests(Report $report, PatientRequest $patient_request)
    {
        $this->authorize('tfd/ajuda de custo listar');
        $patient_requests = $report->patientRequests()
            ->whereNot('id', $patient_request->id)
            ->where('type','Agendamento')
            ->with('report.patientCare.patient','report.cid','report.attachments','attachments','hospitalUnity','medicalProfessional','ownerProfessional','socialProfessional','travelProfessional','costAssistanceProfessional','accountabilityProfessional','paymentProfessional','travels.passengers.patient','travels.passengers.escort','costAssistances.costAssistanceDailies.dailyCost', 'accountabilities.accountabilityDailies.dailyCost', 'paymentInfo','paymentAttachments')
            ->orderBy('id','desc')
            ->get();
        return response()->json($patient_requests, 200);
    }

    public function movePatientRequestFromHistory(PatientRequest $patient_request, CostAssistanceService $costAssistanceService)
    {
        $this->authorize('tfd/ajuda de custo atualizar');
        return $costAssistanceService->movePatientRequestFromHistory($patient_request);
    }

    public function movePatientRequestFromProcesses(PatientRequest $patient_request, CostAssistanceService $costAssistanceService)
    {
        $this->authorize('tfd/ajuda de custo atualizar');
        return $costAssistanceService->movePatientRequestFromProcesses($patient_request);
    }

    public function movePatientRequestFromOthers(PatientRequest $patient_request, CostAssistanceService $costAssistanceService)
    {
        $this->authorize('tfd/ajuda de custo atualizar');
        return $costAssistanceService->movePatientRequestFromOthers($patient_request);
    }

    public function undoPatientRequest(PatientRequest $patient_request, Request $request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/ajuda de custo atualizar');
        return $patientRequestService->undoPatientRequest($patient_request, $request, 'cost assistance');
    }

    public function getPaymentProfessionals()
    {
        $this->authorize('tfd/ajuda de custo listar');
        $payment_profissionals = Professional::query()
            ->with('user')
            ->withCount('patientPaymentRequests')
            ->where('type','Pagamento')
            ->get();
        return response()->json($payment_profissionals, 200);
    }

    public function processPatientRequestToPayment(PatientRequest $patient_request, Request $request, CostAssistanceService $costAssistanceService)
    {
        $this->authorize('tfd/ajuda de custo atualizar');
        return $costAssistanceService->processPatientRequestToPayment($patient_request, $request);
    }

    public function finishBackPatientRequest(PatientRequest $patient_request, CostAssistanceService $costAssistanceService)
    {
        $this->authorize('tfd/ajuda de custo atualizar');
        return $costAssistanceService->finishBackPatientRequest($patient_request);
    }

}
