<?php

namespace App\Services;

use App\Models\Opinion;
use App\Models\PatientRequest;
use App\Models\Professional;
use App\Models\Report;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpinionService
{
    private function tfd()
    {
        return DB::connection(); 
    }

    public function createOpinion(PatientRequest $patient_request, Request $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $patient_request->opinions()->create([
                'professional_id' => Professional::where('user_id', auth()->user()->id)->first()->id,
                'name' => $request->name,
                'content' => $request->content,
                'is_approved' => $request->is_approved,
            ]);
            $patient_request->report()->update(['is_editable' => false]);
            $this->tfd()->commit();
            return response()->json(['message' => 'Parecer criado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateOpinion(Opinion $opinion, Request $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $opinion->update($request->all());
            $report = Report::find($opinion->patientRequest->report_id);
            $report->update(['is_editable' => false]);
            $this->tfd()->commit();
            return response()->json(['message' => 'Parecer atualizado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteOpinion(Opinion $opinion)
    {
        try {
            $this->tfd()->beginTransaction();
            $opinion->delete();
            $this->tfd()->commit();
            return response()->json(['message' => 'Parecer deletado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function processPatientRequestToSocial(PatientRequest $patient_request, Request $request)
    {
        try {
            $patient_request->update([
                'social_professional_id' => $request->social_professional_id,
            ]);
            return response()->json(['message' => 'Tramitação feita com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function haltedPatientRequest($type, PatientRequest $patient_request)
    {
        try {
            if ($type == 'medical') {
                $patient_request->update(['is_medical_bookmark' => !$patient_request->is_medical_bookmark]);
                return response()->json(['message' => $patient_request->is_medical_bookmark ? 'Solicitação marcada em sobrestado.' : 'Solicitação desmarcada em sobrestado.'], 200);
            } else {
                $patient_request->update(['is_social_bookmark' => !$patient_request->is_social_bookmark]);
                return response()->json(['message' => $patient_request->is_social_bookmark ? 'Solicitação marcada em sobrestado.' : 'Solicitação desmarcada em sobrestado.'], 200);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function movePatientRequestFromProcesses($type, PatientRequest $patient_request)
    {
        try {
            if ($type == 'medical') {
                $patient_request->update([
                    'social_professional_id' => null,
                ]);
            } else {
                $patient_request->update([
                    'cost_assistance_professional_id' => null,
                    'travel_professional_id' => null,
                ]);
            }
            return response()->json(['message' => 'Solicitação transferida com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function movePatientRequestFromArchive($type, PatientRequest $patient_request)
    {
        try {
            if ($type == 'medical') {
                $patient_request->update([
                    'back_to_medical' => 'Retirou do arquivo',
                ]);
            } else {
                $patient_request->update([
                    'back_to_social' => 'Retirou do arquivo',
                ]);
            }
            $patient_request->update(['is_opinion_archived' => false]);
            return response()->json(['message' => 'Solicitação retirada do arquivo.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function movePatientRequestFromOthers($type, PatientRequest $patient_request)
    {
        try {
            if ($type == 'medical') {
                $patient_request->update([
                    'medical_professional_id' => Professional::where('user_id',auth()->user()->id)->first()->id,
                ]);
            } else {
                $patient_request->update([
                    'social_professional_id' => Professional::where('user_id',auth()->user()->id)->first()->id,
                ]);
            }
            return response()->json(['message' => 'Solicitação transferida com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function processPatientRequestToCostAssistanceAndTravel(PatientRequest $patient_request, Request $request)
    {
        try {
            $patient_request->update([
                'cost_assistance_professional_id' => $request->cost_assistance_professional_id,
                'travel_professional_id' => $request->travel_professional_id,
            ]);
            return response()->json(['message' => 'Solicitação tramitada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function finishBackPatientRequest($type, PatientRequest $patient_request)
    {
        try {
            if ($type == 'medical') {
                $patient_request->update(['back_to_medical' => null]);
            } else {
                $patient_request->update(['back_to_social' => null]);
            }
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