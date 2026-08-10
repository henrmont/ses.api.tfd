<?php

namespace App\Services;

use App\Models\Accountability;
use App\Models\AccountabilityDaily;
use App\Models\PatientRequest;
use App\Models\Professional;
use Exception;
use Illuminate\Http\Request;

class AccountabilityService
{
    public function haltedPatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->update(['is_accountability_bookmark' => !$patient_request->is_accountability_bookmark]);
            return response()->json(['message' => $patient_request->is_accountability_bookmark ? 'Solicitação marcada em sobrestado.' : 'Solicitação desmarcada em sobrestado.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function createAccountability(PatientRequest $patient_request, Request $request)
    {
        try {
            $patient_request->accountabilities()->create($request->all());
            return response()->json(['message' => 'Prestação de conta criada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateAccountability(Accountability $accountability, Request $request)
    {
        try {
            $accountability->update($request->all());
            return response()->json(['message' => 'Prestação de conta atualizada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteAccountability(Accountability $accountability)
    {
        try {
            $accountability->delete();
            return response()->json(['message' => 'Prestação de conta deletada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function createAccountabilityDaily(Accountability $accountability, Request $request)
    {
        try {
            $accountability->accountabilityDailies()->create($request->all());
            return response()->json(['message' => 'Diária criada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateAccountabilityDaily(AccountabilityDaily $accountability_daily, Request $request)
    {
        try {
            $accountability_daily->update($request->all());
            return response()->json(['message' => 'Diária atualizada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteAccountabilityDaily(AccountabilityDaily $accountability_daily)
    {
        try {
            $accountability_daily->delete();
            return response()->json(['message' => 'Diária deletada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function movePatientRequestFromArchive(PatientRequest $patient_request)
    {
        try {
            $patient_request->update([
                'back_to_accountability' => 'Retirou do arquivo',
                'back_to_cost_assistance' => 'Retirou do arquivo',
            ]);
            $patient_request->update(['is_cost_assistance_archived' => false]);
            return response()->json(['message' => 'Solicitação retirada do arquivo.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // AQUI
    public function movePatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->update([
                'accountability_professional_id' => Professional::where('user_id',auth()->user()->id)->first()->id,
                'is_cost_assistance_archived' => false
            ]);
            return response()->json(['message' => 'Solicitação transferida com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function archivePatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->update(['is_cost_assistance_archived' => true]);
            return response()->json(['message' => 'Solicitação arquivada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function finishPatientRequestAccountability(PatientRequest $patient_request)
    {
        try {
            $patient_request->update(['is_accountability_finished' => true]);
            return response()->json(['message' => 'Solicitação finalizada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    

    

}