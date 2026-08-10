<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\PatientCare;
use App\Models\PatientRequest;
use App\Models\PatientRequestAttachment;
use App\Models\Professional;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientRequestService
{
    private function tfd()
    {
        return DB::connection(); 
    }

    private function storage()
    {
        return DB::connection('storage');
    }

    private function storeArchive($file) 
    {
        try {
            $fileContents = file_get_contents($file->getRealPath());
            $base64String = base64_encode($fileContents);
            $mimeType = $file->getClientMimeType();
            $dataUri = "data:{$mimeType};base64,{$base64String}";

            $archive = Archive::on('storage')->create(['archive' => $dataUri]);
            return $archive->id;
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function createPatientRequest(Request $request)
    {
        try {
            PatientRequest::create([
                'report_id' => $request->report_id,
                'type' => $request->type,
                'consultation_date' => $request->consultation_date,
                'observation' => $request->observation,
                'hospital_unity_id' => $request->hospital_unity_id,
                'owner_professional_id' => Professional::where('user_id',auth()->user()->id)->first()->id,
            ]);
            return response()->json(['message' => 'Solicitação criada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function haltedPatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->update(['is_owner_bookmark' => !$patient_request->is_owner_bookmark]);
            return response()->json(['message' => $patient_request->is_owner_bookmark ? 'Solicitação marcada em sobrestado.' : 'Solicitação desmarcada em sobrestado.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updatePatientRequest(PatientRequest $patient_request, Request $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $patient_request->update($request->except('owner_professional_id'));
            if (!$request->consultation_date)
                $patient_request->update(['consultation_date' => null]);    
            $this->tfd()->commit();
            return response()->json(['message' => 'Solicitação atualizada com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deletePatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->delete();
            return response()->json(['message' => 'Solicitação deletada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function createPatientRequestAttachment(PatientRequest $patient_request, Request $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $this->storage()->beginTransaction();
            $patient_request->attachments()->create([
                'name' => $request->name,
                'archive_id' => $this->storeArchive($request->file('file'))
            ]);
            $this->tfd()->commit();
            $this->storage()->commit();
            return response()->json(['message' => 'Anexo criado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            $this->storage()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updatePatientRequestAttachment(PatientRequestAttachment $patient_request_attachment, Request $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $this->storage()->beginTransaction();
            $patient_request_attachment->update(['name' => $request->name]);
            if ($request->file('file'))
                $patient_request_attachment->update(['archive_id' => $this->storeArchive($request->file('file'))]);
            $this->tfd()->commit();
            $this->storage()->commit();
            return response()->json(['message' => 'Anexo atualizado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            $this->storage()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deletePatientRequestAttachment(PatientRequestAttachment $patient_request_attachment)
    {
        try {
            $this->tfd()->beginTransaction();
            $patient_request_attachment->delete();
            $this->tfd()->commit();
            return response()->json(['message' => 'Anexo deletado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function processPatientRequestToMedical(PatientRequest $patient_request, Request $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $patient_request->update([
                'medical_professional_id' => $request->medical_professional_id,
            ]);
            $patient_request->report()->update(['is_editable' => false]);
            $this->tfd()->commit();
            return response()->json(['message' => 'Solicitação tramitada com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function movePatientRequestFromProcesses(PatientRequest $patient_request)
    {
        try {
            $patient_request->update([
                'medical_professional_id' => null,
                'back_to_medical' => null,
                'is_editable' => true
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
                'owner_professional_id' => Professional::where('user_id',auth()->user()->id)->first()->id,
                'medical_professional_id' => null,
                'is_editable' => true
            ]);
            return response()->json(['message' => 'Solicitação transferida com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function movePatientRequestFromArchive(PatientRequest $patient_request)
    {
        try {
            $patient_request->update([
                'owner_professional_id' => Professional::where('user_id',auth()->user()->id)->first()->id,
                'is_archived' => false
            ]);
            return response()->json(['message' => 'Solicitação transferida com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function undoPatientRequest(PatientRequest $patient_request, Request $request, ?string $way = null)
    {
        try {
            switch ($request->to) {
                case 'user':
                    $patient_request->report()->update(['is_editable' => true]);
                    $patient_care = PatientCare::find($patient_request->report->patient_care_id);
                    $patient_care->update(['back_to_user' => $patient_care->back_to_user.'; '.$request->reason, 'is_archived' => false]);
                    if ($way)
                        $this->changeWay($patient_request, $patient_request->owner_professional_id, $way);
                    break;
                case 'owner':
                    $patient_request->update(['back_to_owner' => $patient_request->back_to_owner.'; '.$request->reason]);
                    if ($way)
                        $this->changeWay($patient_request, $patient_request->owner_professional_id, $way);
                    break;
                case 'medical':
                    $patient_request->update(['back_to_medical' => $patient_request->back_to_medical.'; '.$request->reason]);
                    if ($way)
                        $this->changeWay($patient_request, $patient_request->medical_professional_id, $way);
                    break;
                case 'social':
                    $patient_request->update(['back_to_social' => $patient_request->back_to_social.'; '.$request->reason]);
                    if ($way)
                        $this->changeWay($patient_request, $patient_request->social_professional_id, $way);
                    break;
                case 'travel':
                    $patient_request->update(['back_to_travel' => $patient_request->back_to_travel.'; '.$request->reason]);
                    if ($way)
                        $this->changeWay($patient_request, $patient_request->travel_professional_id, $way);
                    break;
                case 'cost_assistance':
                    $patient_request->update(['back_to_cost_assistance' => $patient_request->back_to_cost_assistance.'; '.$request->reason]);
                    if ($way)
                        $this->changeWay($patient_request, $patient_request->cost_assistance_professional_id, $way);
                    break;
            }
            return response()->json(['message' => 'Solicitação retornada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function archiveOpinionPatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->update([
                'is_opinion_archived' => true,
            ]);
            return response()->json(['message' => 'Solicitação arquivada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function archiveTravelPatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->update([
                'is_travel_archived' => true,
            ]);
            return response()->json(['message' => 'Solicitação arquivada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function archiveAccountabilityPatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->update([
                'is_accountability_archived' => true,
            ]);
            return response()->json(['message' => 'Solicitação arquivada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function archivePaymentPatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->update([
                'is_payment_archived' => true,
            ]);
            return response()->json(['message' => 'Solicitação arquivada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function finishBackPatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->update(['back_to_owner' => null]);
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

    private function changeWay(PatientRequest $patient_request, int $professional_id, string $way)
    {
        switch ($way) {
            case 'travel':
                $patient_request->update(['back_from_travel' => $professional_id]);
                break;
            case 'cost assistance':
                $patient_request->update(['back_from_cost_assistance' => $professional_id]);
                break;
        }
    }

}