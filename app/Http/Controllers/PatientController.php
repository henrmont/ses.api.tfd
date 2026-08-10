<?php

namespace App\Http\Controllers;

use App\Models\Cid;
use App\Models\Escort;
use App\Models\Patient;
use App\Models\PatientCare;
use App\Models\PatientCareEscort;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Services\PatientService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    use AuthorizesRequests;

    public function getPatients()
    {
        $this->authorize('tfd/paciente listar');
        $patient_cares = PatientCare::query()
            ->tfd()
            ->where('is_archived', false)
            ->with('patient.patientInfo','user.professional')
            ->orderBy('id','desc')
            ->get();
        return response()->json($patient_cares, 200);
    }

    public function getArchivePatients()
    {
        $this->authorize('tfd/paciente listar');
        $patient_cares = PatientCare::query()
            ->tfd()
            ->where('is_archived', true)
            ->with('patient.patientInfo','user.professional')
            ->orderBy('id','desc')
            ->get();
        return response()->json($patient_cares, 200);
    }

    public function createPatient(Request $request, PatientService $patientService)
    {
        $this->authorize('tfd/paciente criar');
        return $patientService->createPatient($request);
    }

    public function updatePatient(PatientCare $patient_care, Request $request, PatientService $patientService)
    {
        $this->authorize('tfd/paciente atualizar');
        return $patientService->updatePatient($patient_care, $request);
    }

    public function getPatientEscorts(PatientCare $patient_care)
    {
        $this->authorize('tfd/paciente acompanhantes');
        $escorts = $patient_care->escorts()
            ->orderBy('id','asc')
            ->get();
        return response()->json($escorts, 200);
    }

    public function createPatientEscort(PatientCare $patient_care, Request $request, PatientService $patientService)
    {
        $this->authorize('tfd/paciente acompanhantes');
        return $patientService->createEscort($patient_care, $request);
    }

    public function updatePatientEscort(Escort $escort, Request $request, PatientService $patientService)
    {
        $this->authorize('tfd/paciente acompanhantes');
        return $patientService->updateEscort($escort, $request);
    }

    public function deletePatientEscort(PatientCareEscort $patient_care_escort, PatientService $patientService)
    {
        $this->authorize('tfd/paciente acompanhantes');
        return $patientService->deleteEscort($patient_care_escort);
    }

    public function getPatientReports(PatientCare $patient_care)
    {
        $this->authorize('tfd/paciente laudos');
        $reports = $patient_care->reports()
            ->with('patientCare','cid','attachments')
            ->orderBy('id','asc')
            ->get();
        return response()->json($reports, 200);
    }

    public function getCids(PatientCare $patient_care)
    {
        $this->authorize('tfd/paciente laudos');
        $cids = Cid::query()
            ->whereNotIn('id', $patient_care->reports()->pluck('cid_id')->toArray())
            ->orderBy('id','asc')
            ->get();
        return response()->json($cids, 200);
    }

    public function createPatientReport(PatientCare $patient_care, Request $request, PatientService $patientService)
    {
        $this->authorize('tfd/paciente laudos');
        return $patientService->createReport($patient_care, $request);
    }

    public function updatePatientReport(Report $report, Request $request, PatientService $patientService)
    {
        $this->authorize('tfd/paciente laudos');
        return $patientService->updateReport($report, $request);
    }

    public function deletePatientReport(Report $report, PatientService $patientService)
    {
        $this->authorize('tfd/paciente laudos');
        return $patientService->deleteReport($report);
    }

    public function getReportAttachments(Report $report)
    {
        $this->authorize('tfd/paciente laudos');
        $attachments = $report->attachments()
            ->orderBy('id','asc')
            ->get();
        return response()->json($attachments, 200);
    }

    public function createReportAttachment(Report $report, Request $request, PatientService $patientService)
    {
        $this->authorize('tfd/paciente laudos');
        return $patientService->createReportAttachment($report, $request);
    }

    public function updateReportAttachment(ReportAttachment $report_attachment, Request $request, PatientService $patientService)
    {
        $this->authorize('tfd/paciente laudos');
        return $patientService->updateReportAttachment($report_attachment, $request);
    }

    public function deleteReportAttachment(ReportAttachment $report_attachment, PatientService $patientService)
    {
        $this->authorize('tfd/paciente laudos');
        return $patientService->deleteReportAttachment($report_attachment);
    }

    public function archivePatient(PatientCare $patient_care, PatientService $patientService)
    {
        $this->authorize('tfd/paciente atualizar');
        return $patientService->archivePatient($patient_care);
    }

    public function movePatientFromArchive(PatientCare $patient_care, PatientService $patientService)
    {
        $this->authorize('tfd/paciente atualizar');
        return $patientService->movePatientFromArchive($patient_care);
    }

    public function movePatientFromOthers(PatientCare $patient_care, PatientService $patientService)
    {
        $this->authorize('tfd/paciente atualizar');
        return $patientService->movePatientFromOthers($patient_care);
    }

    public function validatePatient(PatientCare $patient_care, PatientService $patientService)
    {
        $this->authorize('tfd/paciente validar');
        return $patientService->validatePatient($patient_care);
    }

    public function finishBackPatient(PatientCare $patient_care, PatientService $patientService)
    {
        $this->authorize('tfd/paciente atualizar');
        return $patientService->finishBackPatient($patient_care);
    }

    // checks
    public function getPatientCns($cns)
    {
        $this->authorize('tfd/paciente listar');
        $patient = Patient::query()
            ->where('cns', $cns)
            ->firstOrFail();
        return response()->json($patient, 200);
    }

    public function getPatientDocument($document)
    {
        $this->authorize('tfd/paciente listar');
        $patient = Patient::query()
            ->where('document', $document)
            ->firstOrFail();
        return response()->json($patient, 200);
    }

    public function getEscortCns($cns)
    {
        $this->authorize('tfd/paciente acompanhantes');
        $escort = Escort::query()
            ->where('cns', $cns)
            ->firstOrFail();
        return response()->json($escort, 200);
    }

    public function getEscortDocument($document)
    {
        $this->authorize('tfd/paciente acompanhantes');
        $escort = Escort::query()
            ->where('document', $document)
            ->firstOrFail();
        return response()->json($escort, 200);
    }

    // validators
    public function cnsPatientExists($cns, $data = null)
    {
        $this->authorize('tfd/paciente listar');
        $exists = Patient::query()
            ->whereHas('patientCares', function ($q) {
                $q->tfd();
            })
            ->where('cns', $cns);
        if ($data)
            $exists->whereNot('cns', $data);
        $exists = $exists->exists();
        return response()->json($exists, 200);
    }

    public function cnsEscortExists(PatientCare $patient_care, $cns, $data = null)
    {
        $this->authorize('tfd/paciente acompanhantes');
        $exists = $patient_care->escorts()
            ->where('cns', $cns);
        if ($data)
            $exists->whereNot('cns', $data);
        $exists = $exists->exists();
        return response()->json($exists, 200);
    }

    public function documentPatientExists($document, $data = null)
    {
        $this->authorize('tfd/paciente listar');
        $exists = Patient::query()
            ->whereHas('patientCares', function ($q) {
                $q->tfd();
            })
            ->where('document', $document);
        if ($data)
            $exists->whereNot('document', $data);
        $exists = $exists->exists();
        return response()->json($exists, 200);
    }

    public function documentEscortExists(PatientCare $patient_care, $document, $data = null)
    {
        $this->authorize('tfd/paciente acompanhantes');
        $exists = $patient_care->escorts()
            ->where('document', $document);
        if ($data)            
            $exists->whereNot('document', $data);
        $exists = $exists->exists();
        return response()->json($exists, 200);
    }
}
