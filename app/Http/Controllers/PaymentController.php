<?php

namespace App\Http\Controllers;

use App\Models\BudgetAllocation;
use App\Models\PatientRequest;
use App\Models\Payment;
use App\Services\PatientRequestService;
use App\Services\PaymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected PaymentService $paymentService,
        protected PatientRequestService $patientRequestService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Gestão de Pagamentos do TFD
    |--------------------------------------------------------------------------
    */

    /**
     * Listar pagamentos pendentes e dotação orçamentária.
     */
    public function getPayments(): JsonResponse
    {
        $this->authorize('tfd/pagamento listar');

        $payments = Payment::query()
            ->whereHas('patientRequest', function ($query) {
                $query->notPatientBack()
                    ->whereNull('back_to_cost_assistance')
                    ->whereNull('back_to_owner')
                    ->whereNull('back_to_medical')
                    ->whereNull('back_to_social')
                    ->whereNull('back_to_travel');
            })
            ->where('is_payment_archived', false)
            ->with($this->getPaymentRelations())
            ->latest('id')
            ->get();

        $budgetAllocation = BudgetAllocation::first();

        return response()->json([
            'payments'          => $payments,
            'budget_allocation' => $budgetAllocation,
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Listar pagamentos arquivados no setor de pagamentos.
     */
    public function getArchivePayments(): JsonResponse
    {
        $this->authorize('tfd/pagamento listar');

        $payments = Payment::query()
            ->whereHas('patientRequest', function ($query) {
                $query->notPatientBack()
                    ->whereNull('back_to_cost_assistance')
                    ->whereNull('back_to_owner')
                    ->whereNull('back_to_medical')
                    ->whereNull('back_to_social')
                    ->whereNull('back_to_travel');
            })
            ->where('is_payment_archived', true)
            ->with($this->getPaymentRelations())
            ->latest('id')
            ->get();

        $budgetAllocation = BudgetAllocation::first();

        return response()->json([
            'payments'          => $payments,
            'budget_allocation' => $budgetAllocation,
        ], JsonResponse::HTTP_OK);
    }

    /*
    |--------------------------------------------------------------------------
    | Tramitação e Mudança de Estados do Pagamento
    |--------------------------------------------------------------------------
    */

    /**
     * Sobrestar/paralisar a solicitação de pagamento.
     */
    public function haltedPayment(Payment $payment)
    {
        $this->authorize('tfd/pagamento atualizar');

        return $this->paymentService->haltedPayment($payment);
    }

    /**
     * Atualizar os dados do pagamento.
     */
    public function updatePayment(Payment $payment, Request $request)
    {
        $this->authorize('tfd/pagamento atualizar');

        return $this->paymentService->updatePayment($payment, $request);
    }

    /**
     * Desfazer ação / devolver solicitação no fluxo de pagamentos.
     */
    public function undoPatientRequest(PatientRequest $patient_request, Request $request)
    {
        $this->authorize('tfd/pagamento atualizar');

        return $this->patientRequestService->undoPatientRequest($patient_request, $request);
    }

    /**
     * Arquivar a solicitação de pagamento.
     */
    public function archivePayment(Payment $payment)
    {
        $this->authorize('tfd/pagamento atualizar');

        return $this->paymentService->archivePayment($payment);
    }

    /**
     * Mover solicitação a partir do arquivo.
     */
    public function movePaymentFromArchive(Payment $payment)
    {
        $this->authorize('tfd/pagamento atualizar');

        return $this->paymentService->movePaymentFromArchive($payment);
    }

    /**
     * Mover solicitação a partir do setor "Outros".
     */
    public function movePaymentFromOthers(Payment $payment)
    {
        $this->authorize('tfd/pagamento atualizar');

        return $this->paymentService->movePaymentFromOthers($payment);
    }

    /*
    |--------------------------------------------------------------------------
    | Emissão de Documentos e PDFs
    |--------------------------------------------------------------------------
    */

    /**
     * Baixar PDF mesclado completo da solicitação.
     */
    public function downloadMergedPdf(Payment $payment)
    {
        $this->authorize('tfd/pagamento listar');

        return $this->paymentService->downloadMergedPdf($payment);
    }

    /**
     * Baixar PDF do Memorando de pagamento.
     */
    public function downloadMemoPdf(Payment $payment)
    {
        $this->authorize('tfd/pagamento listar');

        return $this->paymentService->downloadMemoPdf($payment);
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos Auxiliares
    |--------------------------------------------------------------------------
    */

    /**
     * Retorna a lista padrão de relacionamentos com eager-loading.
     */
    private function getPaymentRelations(): array
    {
        return [
            'costAssistance.costAssistanceDailies.dailyCost',
            'travel.passengers.escort',
            'paymentProfessional',
            'paymentAttachments',
            'patientRequest.report.patientCare.patient',
            'patientRequest.report.patientCare.user.professional',
            'patientRequest.report.cid',
            'patientRequest.report.attachments',
            'patientRequest.attachments',
            'patientRequest.hospitalUnity',
            'patientRequest.medicalProfessional',
            'patientRequest.ownerProfessional',
            'patientRequest.socialProfessional',
            'patientRequest.travelProfessional',
            'patientRequest.costAssistanceProfessional',
            'patientRequest.accountabilityProfessional',
            'patientRequest.travels.passengers.patient',
            'patientRequest.travels.passengers.escort',
            'patientRequest.costAssistances.costAssistanceDailies.dailyCost',
            'patientRequest.accountabilities.accountabilityDailies.dailyCost',
        ];
    }
}