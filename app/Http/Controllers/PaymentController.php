<?php

namespace App\Http\Controllers;

use App\Models\PatientRequest;
use App\Services\PatientRequestService;
use App\Services\PaymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use AuthorizesRequests;
    
    public function getPatientRequests()
    {
        $this->authorize('tfd/pagamento listar');
        $patient_requests = PatientRequest::query()
            ->whereNull('back_to_owner')
            ->whereNull('back_to_medical')
            ->whereNull('back_to_social')
            ->whereNull('back_to_cost_assistance')
            ->where('is_archived', false)
            ->where('type','Agendamento')
            ->with('report.patientCare.patient','report.cid','report.attachments','attachments','hospitalUnity','medicalProfessional','ownerProfessional','socialProfessional','travelProfessional','costAssistanceProfessional','accountabilityProfessional','paymentProfessional','travels.passengers.patient','travels.passengers.escort','costAssistances.costAssistanceDailies.dailyCost', 'accountabilities.accountabilityDailies.dailyCost', 'paymentInfo','paymentAttachments')
            ->orderBy('id','desc')
            ->get();
        return response()->json($patient_requests, 200);
    }

    public function haltedPatientRequest(PatientRequest $patient_request, PaymentService $paymentService)
    {
        $this->authorize('tfd/pagamento atualizar');
        return $paymentService->haltedPatientRequest($patient_request);
    }

    public function paymentInfo(PatientRequest $patient_request, Request $request, PaymentService $paymentService)
    {
        $this->authorize('tfd/pagamento atualizar');
        return $paymentService->paymentInfo($patient_request, $request);
    }

    public function finishPatientRequestPayment(PatientRequest $patient_request, PaymentService $paymentService)
    {
        $this->authorize('tfd/pagamento atualizar');
        return $paymentService->finishPatientRequestPayment($patient_request);  
    }

    public function undoPatientRequest(PatientRequest $patient_request, Request $request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/pagamento atualizar');
        return $patientRequestService->undoPatientRequest($patient_request, $request);
    }
}
