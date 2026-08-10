<?php

namespace App\Http\Controllers;

use App\Models\PatientRequest;
use App\Models\Payment;
use App\Services\PatientRequestService;
use App\Services\PaymentService;
use App\Services\PdfMergerService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    use AuthorizesRequests;
    
    public function getPayments()
    {
        $this->authorize('tfd/pagamento listar');
        $payments = Payment::query()
            ->whereHas('patientRequest', function ($query) {
                $query->notPatientBack()
                    ->whereNull('back_to_cost_assistance')
                    ->where(function ($query) {
                        $query->whereNull('back_to_owner')->orWhereNull('back_from_cost_assistance');
                    })
                    ->where(function ($query) {
                        $query->whereNull('back_to_medical')->orWhereNull('back_from_cost_assistance');
                    })
                    ->where(function ($query) {
                        $query->whereNull('back_to_social')->orWhereNull('back_from_cost_assistance');
                    });
            })
            ->where('is_payment_archived', false)
            ->with('costAssistance.costAssistanceDailies.dailyCost','travel','paymentProfessional','paymentAttachments','patientRequest.report.patientCare.patient','patientRequest.report.cid','patientRequest.report.attachments','patientRequest.attachments','patientRequest.hospitalUnity','patientRequest.medicalProfessional','patientRequest.ownerProfessional','patientRequest.socialProfessional','patientRequest.travelProfessional','patientRequest.costAssistanceProfessional','patientRequest.accountabilityProfessional','patientRequest.travels.passengers.patient','patientRequest.travels.passengers.escort','patientRequest.costAssistances.costAssistanceDailies.dailyCost', 'patientRequest.accountabilities.accountabilityDailies.dailyCost')
            ->orderBy('id','desc')
            ->get();
        return response()->json($payments, 200);
    }

    public function getArchivePatientRequests()
    {
        $this->authorize('tfd/pagamento listar');
        $patient_requests = PatientRequest::query()
            ->notPatientBack()
            ->whereNull('back_to_cost_assistance')
            ->where(function ($query) {
                $query->whereNull('back_to_owner')->orWhereNull('back_from_cost_assistance');
            })
            ->where(function ($query) {
                $query->whereNull('back_to_medical')->orWhereNull('back_from_cost_assistance');
            })
            ->where(function ($query) {
                $query->whereNull('back_to_social')->orWhereNull('back_from_cost_assistance');
            })
            ->where('is_payment_archived', true)
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

    public function updatePayment(Payment $payment, Request $request, PaymentService $paymentService)
    {
        $this->authorize('tfd/pagamento atualizar');
        return $paymentService->updatePayment($payment, $request);
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

    public function archivePatientRequest(PatientRequest $patient_request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/pagamento atualizar');
        return $patientRequestService->archivePaymentPatientRequest($patient_request);
    }

    public function movePatientRequestFromArchive(PatientRequest $patient_request, PaymentService $paymentService)
    {
        $this->authorize('tfd/pagamento atualizar');
        return $paymentService->movePatientRequestFromArchive($patient_request);
    }

    public function downloadMergedPdf(Payment $payment, PdfMergerService $pdfMerger): Response
    {
        $payment->load([
            'paymentAttachments',
            'patientRequest.report.patientCare.patient',
            'patientRequest.report.cid',
            'patientRequest.travels.passengers.patient',
            'patientRequest.travels.passengers.escort',
            'patientRequest.hospitalUnity',
            'patientRequest.medicalProfessional',
            'patientRequest.travelProfessional',
            'costAssistance.passenger.patient',
            'costAssistance.passenger.escort',
            'travel.passengers.patient.fileDocument',
            'travel.passengers.escort.fileDocument',
            'travel.travelRoutes'
        ]);

        // Mapeia os anexos passando o campo 'file_base64'
        $attachments = $payment->paymentAttachments->map(fn($item) => [
            'id'      => $item->patientRequestAttachment->id,
            'content' => $item->patientRequestAttachment->archive->archive
        ])->toArray();

        // Processa a mesclagem em memória
        $pdfBinary = $pdfMerger->generateMergedPdf($payment->toArray(), $attachments);

        $filename = "processo_{$payment->id}_completo.pdf";

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Access-Control-Expose-Headers' => 'Content-Disposition'
        ]);
    }

    public function downloadMemoPdf(Payment $payment, PdfMergerService $pdfMerger): Response
    {
        $payment->load([
            'paymentAttachments',
            'patientRequest.report.patientCare.patient',
            'patientRequest.report.cid',
            'patientRequest.travels.passengers.patient',
            'patientRequest.travels.passengers.escort',
            'patientRequest.hospitalUnity',
            'patientRequest.medicalProfessional',
            'patientRequest.travelProfessional',
            'costAssistance.passenger.patient',
            'costAssistance.passenger.escort',
            'costAssistance.costAssistanceDailies.dailyCost',
            'travel.passengers.patient.fileDocument',
            'travel.passengers.escort.fileDocument',
            'travel.travelRoutes'
        ]);

        // Processa a mesclagem em memória
        $pdfBinary = $pdfMerger->generateMemoPdf($payment->toArray());

        $filename = "memorando_{$payment->document_number}.pdf";

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Access-Control-Expose-Headers' => 'Content-Disposition'
        ]);
    }

    
}
