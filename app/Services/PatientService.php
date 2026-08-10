<?php

namespace App\Services;

use App\Http\Requests\PatientFormRequest;
use App\Models\Archive;
use App\Models\Escort;
use App\Models\Patient;
use App\Models\PatientCare;
use App\Models\PatientCareEscort;
use App\Models\PatientInfo;
use App\Models\Report;
use App\Models\ReportAttachment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientService
{
    private function tfd()
    {
        return DB::connection(); 
    }

    private function core()
    {
        return DB::connection('core');
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

    public function createPatient(Request $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $this->core()->beginTransaction();
            $patient = Patient::where('cns', $request->cns)->orWhere('document', $request->document);
            if ($patient->exists()) {
                $patient->first()->update($request->all());
            } else {
                $patient = Patient::on('core')->create($request->except('observation','control_number'));
            }
            PatientCare::on('core')->create([
                'patient_id' => $patient->id,
                'module_id' => auth()->user()->module_id,
                'user_id' => auth()->user()->id
            ]);
            if ($request->file('file_cns'))
                $patient->update(['file_cns_id' => $this->storeArchive($request->file('file_cns'))]);
            if ($request->file('file_document'))
                $patient->update(['file_document_id' => $this->storeArchive($request->file('file_document'))]);
            if ($request->file('file_deficiency'))
                $patient->update(['file_deficiency_id' => $this->storeArchive($request->file('file_deficiency'))]);
            if ($request->file('file_address'))
                $patient->update(['file_address_id' => $this->storeArchive($request->file('file_address'))]);
            $patient_info = PatientInfo::create([
                'patient_id' => $patient->id,
                'observation' => $request->observation,
                'control_number' => $request->control_number,
            ]);
            if ($request->file('file_protocol'))
                $patient_info->update(['file_protocol_id' => $this->storeArchive($request->file('file_protocol'))]);
            $this->tfd()->commit();
            $this->core()->commit();
            return response()->json(['message' => 'Paciente cadastrado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            $this->core()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updatePatient(PatientCare $patient_care, Request $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $patient = Patient::find($patient_care->patient_id);
            $patient->update($request->except('observation','control_number'));
            if ($request->file('file_cns'))
                $patient->update(['file_cns_id' => $this->storeArchive($request->file('file_cns'))]);
            if ($request->file('file_document'))
                $patient->update(['file_document_id' => $this->storeArchive($request->file('file_document'))]);
            if ($request->file('file_deficiency'))
                $patient->update(['file_deficiency_id' => $this->storeArchive($request->file('file_deficiency'))]);
            if ($request->file('file_address'))
                $patient->update(['file_address_id' => $this->storeArchive($request->file('file_address'))]);
            $patient->patientInfo()->update($request->only('observation','control_number'));
            if ($request->file('file_protocol'))
                $patient->patientInfo()->update(['file_protocol_id' => $this->storeArchive($request->file('file_protocol'))]);
            $this->tfd()->commit();
            return response()->json(['message' => 'Paciente atualizado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function createEscort(PatientCare $patient_care, Request $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $escort = Escort::where('cns', $request->cns)->orWhere('document', $request->document);
            if ($escort->exists()) {
                $escort->first()->update($request->all());
                $escort->first()->patientCareEscort()->create(['patient_care_id' => $patient_care->id]);
            } else {
                $escort = $patient_care->escorts()->create($request->all());
            }
            if ($request->file('file_cns'))
                $escort->update(['file_cns_id' => $this->storeArchive($request->file('file_cns'))]);
            if ($request->file('file_document'))
                $escort->update(['file_document_id' => $this->storeArchive($request->file('file_document'))]);
            if ($request->file('file_address'))
                $escort->update(['file_address_id' => $this->storeArchive($request->file('file_address'))]);
            $this->tfd()->commit();
            return response()->json(['message' => 'Acompanhante criado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateEscort(Escort $escort, Request $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $escort->update($request->all());
            if ($request->file('file_cns'))
                $escort->update(['file_cns_id' => $this->storeArchive($request->file('file_cns'))]);
            if ($request->file('file_document'))
                $escort->update(['file_document_id' => $this->storeArchive($request->file('file_document'))]);
            if ($request->file('file_address'))
                $escort->update(['file_address_id' => $this->storeArchive($request->file('file_address'))]);
            $this->tfd()->commit();
            return response()->json(['message' => 'Acompanhante atualizado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteEscort(PatientCareEscort $patient_care_escort)
    {
        try {
            $this->tfd()->beginTransaction();
            $patient_care_escort->delete();
            $this->tfd()->commit();
            return response()->json(['message' => 'Acompanhante deletado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function createReport(PatientCare $patient_care, $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $patient_care->reports()->create($request->all());
            $this->tfd()->commit();
            return response()->json(['message' => 'Laudo criado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateReport(Report $report, $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $report->update($request->all());
            $this->tfd()->commit();
            return response()->json(['message' => 'Laudo atualizado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteReport(Report $report)
    {
        try {
            $this->tfd()->beginTransaction();
            $report->delete();
            $this->tfd()->commit();
            return response()->json(['message' => 'Laudo deletado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function createReportAttachment(Report $report, $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $this->storage()->beginTransaction();
            $report->attachments()->create([
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

    public function updateReportAttachment(ReportAttachment $report_attachment, $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $this->storage()->beginTransaction();
            $report_attachment->update(['name' => $request->name]);
            if ($request->file('file'))
                $report_attachment->update(['archive_id' => $this->storeArchive($request->file('file'))]);
            $report = Report::find($report_attachment->report_id);
            $this->tfd()->commit();
            $this->storage()->commit();
            return response()->json(['message' => 'Anexo atualizado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            $this->storage()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteReportAttachment(ReportAttachment $report_attachment)
    {
        try {
            $this->tfd()->beginTransaction();
            $report_attachment->delete();
            $report = Report::find($report_attachment->report_id);
            $this->tfd()->commit();
            return response()->json(['message' => 'Anexo deletado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function archivePatient(PatientCare $patient_care)
    {
        try {
            $patient_care->update(['is_archived' => true]);
            return response()->json(['message' => 'Paciente arquivado com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function movePatientFromArchive(PatientCare $patient_care)
    {
        try {
            $patient_care->update([
                'is_archived' => false,
                'user_id' => auth()->user()->id,
                'back_to_user' => 'Retirou do arquivo'
            ]);
            return response()->json(['message' => 'Paciente transferido para sua caixa.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function movePatientFromOthers(PatientCare $patient_care)
    {
        try {
            $patient_care->update([
                'user_id' => auth()->user()->id
            ]);
            return response()->json(['message' => 'Paciente transferido para sua caixa.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function validatePatient(PatientCare $patient_care)
    {
        try {
            $patient_care->update(['is_valid' => !$patient_care->is_valid]);
            return response()->json(['message' => 'Paciente '.($patient_care->is_valid ? 'validado' : 'invalidado').' com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function finishBackPatient(PatientCare $patient_care)
    {
        try {
            $patient_care->update(['back_to_user' => null]);
            return response()->json(['message' => 'Paciente atualizado com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function calcBalance(PatientCare $patient_care)
    {
        $total_costs = 0;
        $total_accountabilities = 0;
        foreach ($patient_care->reports as $report) {
            foreach ($report->patientRequests as $patientRequest) {
                foreach ($patientRequest->costAssistances as $cost_assistance) {
                    $total_costs += $cost_assistance->costAssistanceDailies()->with('dailyCost')->get()->reduce(function ($carry, $item) {
                        return $carry + ($item->amount * $item->dailyCost->value);
                    }, 0);
                }
                foreach ($patientRequest->accountabilities as $accountability) {
                    $total_accountabilities += $accountability->accountabilityDailies()->with('dailyCost')->get()->reduce(function ($carry, $item) {
                        return $carry + ($item->amount * $item->dailyCost->value);
                    }, 0);
                }
            }
        }
        $balance = $total_costs - $total_accountabilities;
        return response()->json($balance, 200);
    }

}