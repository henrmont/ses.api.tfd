<?php

namespace App\Services;

use App\Models\CostAssistance;
use App\Models\CostAssistanceDaily;
use App\Models\PatientRequest;
use App\Models\Payment;
use App\Models\Professional;
use App\Models\Travel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CostAssistanceService
{
    private function tfd()
    {
        return DB::connection(); 
    }

    public function haltedPatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->update(['is_cost_assistance_bookmark' => !$patient_request->is_cost_assistance_bookmark]);
            return response()->json(['message' => $patient_request->is_cost_assistance_bookmark ? 'Solicitação marcada em sobrestado.' : 'Solicitação desmarcada em sobrestado.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function createCostAssistance(PatientRequest $patient_request, Request $request)
    {
        try {
            $patient_request->costAssistances()->create($request->all());
            return response()->json(['message' => 'Ajuda de custo criada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateCostAssistance(CostAssistance $cost_assistance, Request $request)
    {
        try {
            $cost_assistance->update($request->all());
            return response()->json(['message' => 'Ajuda de custo atualizada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteCostAssistance(CostAssistance $cost_assistance)
    {
        try {
            $cost_assistance->delete();
            return response()->json(['message' => 'Ajuda de custo deletada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function createCostAssistanceDaily(CostAssistance $cost_assistance, $request)
    {
        try {
            $cost_assistance->costAssistanceDailies()->create($request->all());
            return response()->json(['message' => 'Diária criada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateCostAssistanceDaily(CostAssistanceDaily $cost_assistance_daily, $request)
    {
        try {
            $cost_assistance_daily->update($request->all());
            return response()->json(['message' => 'Diária atualizada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteCostAssistanceDaily(CostAssistanceDaily $cost_assistance_daily)
    {
        try {
            $cost_assistance_daily->delete();
            return response()->json(['message' => 'Diária deletada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function movePatientRequestFromHistory(PatientRequest $patient_request)
    {
        try {
            $patient_request->update([
                'accountability_professional_id' => Professional::where('user_id',auth()->user()->id)->first()->id,
            ]);
            return response()->json(['message' => 'Solicitação transferida com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function movePatientRequestFromProcesses(PatientRequest $patient_request)
    {
        try {
            $patient_request->update([
                'payment_professional_id' => null,
                'back_to_payment' => null
            ]);
            return response()->json(['message' => 'Solicitação transferida com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function movePatientRequestFromOthers(PatientRequest $patient_request)
    {
        try {
            $patient_request->update([
                'cost_assistance_professional_id' => Professional::where('user_id',auth()->user()->id)->first()->id,
            ]);
            return response()->json(['message' => 'Solicitação transferida com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function processPatientRequestToPayment(PatientRequest $patient_request, Request $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $payment = Payment::updateOrCreate(
                [
                    'patient_request_id' => $patient_request->id,
                    'cost_assistance_id' => $request->cost_assistance_id,
                    'travel_id'          => $request->travel_id,
                ],
                [
                    'payment_professional_id' => $request->payment_professional_id
                ]
            );

            // 2. Limpa os anexos antigos do pagamento e recria os novos
            $payment->paymentAttachments()->delete();

            if (!empty($request->attachments)) {
                foreach ($request->attachments as $attachment) {
                    $attachmentId = $attachment['file_id'] ?? $attachment['id'] ?? null;

                    if ($attachmentId) {
                        $payment->paymentAttachments()->create([
                            'patient_request_attachment_id' => $attachmentId
                        ]);
                    }
                }
            }

            // 3. Efetiva as alterações na transação
            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação tramitada com sucesso.'], 200);

        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // aqui
    public function movePatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->update([
                'cost_assistance_professional_id' => Professional::where('user_id',auth()->user()->id)->first()->id,
                'is_cost_assistance_archive' => false
            ]);
            return response()->json(['message' => 'Solicitação transferida com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function archivePatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->update(['is_cost_assistance_archive' => true]);
            return response()->json(['message' => 'Solicitação arquivada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function finishBackPatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->update(['back_to_cost_assistance' => null]);
            $professionalId = Professional::where('user_id',auth()->user()->id)->first()->id;
            if ($patient_request->back_from_travel == $professionalId)
                $patient_request->update(['back_from_travel' => null]);
            if ($patient_request->back_from_cost_assistance == $professionalId)
                $patient_request->update(['back_from_cost_assistance' => null]);
            return response()->json(['message' => 'Solicitação atualizada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    

}