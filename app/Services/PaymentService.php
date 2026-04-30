<?php

namespace App\Services;

use App\Models\PatientRequest;
use App\Models\Payment;
use App\Models\Professional;
use Exception;
use Illuminate\Http\Request;

class PaymentService
{
    public function haltedPatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->update(['is_payment_bookmark' => !$patient_request->is_payment_bookmark]);
            return response()->json(['message' => $patient_request->is_payment_bookmark ? 'Solicitação marcada em sobrestado.' : 'Solicitação desmarcada em sobrestado.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function paymentInfo(PatientRequest $patient_request, Request $request)
    {
        try {
            $patient_request->paymentInfo()->updateOrCreate(['description' => $request->old], $request->all());
            return response()->json(['message' => 'Descrição do pagamento atualizada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function finishPatientRequestPayment(PatientRequest $patient_request)
    {
        try {
            $patient_request->update(['is_payment_finished' => true]);
            return response()->json(['message' => 'Solicitação finalizada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    
}