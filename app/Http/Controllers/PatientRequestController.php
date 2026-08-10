<?php

namespace App\Http\Controllers;

use App\Models\HospitalUnity;
use App\Models\PatientCare;
use App\Models\PatientRequest;
use App\Models\PatientRequestAttachment;
use App\Models\Professional;
use App\Services\PatientRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class PatientRequestController extends Controller
{
    use AuthorizesRequests;

    public function getPatientRequests()
    {
        $this->authorize('tfd/solicitação listar');
        $patient_requests = PatientRequest::query()
            ->notPatientBack()
            ->where('is_opinion_archived', false)
            ->with('report.patientCare.patient','report.cid','report.attachments','attachments','hospitalUnity','medicalProfessional','ownerProfessional','socialProfessional','travelProfessional','costAssistanceProfessional','accountabilityProfessional','travels.passengers.patient','travels.passengers.escort','costAssistances.costAssistanceDailies.dailyCost', 'accountabilities.accountabilityDailies.dailyCost')
            ->orderBy('id','desc')
            ->get();
        return response()->json($patient_requests, 200);
    }

    public function getPatients()
    {
        $this->authorize('tfd/solicitação listar');
        $patient_cares = PatientCare::query()
            ->tfd()
            ->with('patient')
            ->orderBy('id','desc')
            ->get();
        return response()->json($patient_cares, 200);
    }

    public function getPatientReports(PatientCare $patient_care)
    {
        $this->authorize('tfd/solicitação listar');
        $reports = $patient_care->reports()
            ->with('cid')
            ->orderBy('id','desc')
            ->get();
        return response()->json($reports, 200);
    }

    public function getHospitalUnities()
    {
        $this->authorize('tfd/solicitação listar');
        $hospital_unities = HospitalUnity::query()
            ->orderBy('id','desc')
            ->get();
        return response()->json($hospital_unities, 200);
    }

    public function getMedicalProfessionals()
    {
        $this->authorize('tfd/solicitação listar');
        $medical_profissionals = Professional::query()
            ->with('user')
            ->withCount('patientMedicalRequests')
            ->where('type','Médico')
            ->get();
        return response()->json($medical_profissionals, 200);
    }

    public function createPatientRequest(Request $request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/solicitação criar');
        return $patientRequestService->createPatientRequest($request);
    }

    public function haltedPatientRequest(PatientRequest $patient_request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/solicitação atualizar');
        return $patientRequestService->haltedPatientRequest($patient_request);
    }

    public function updatePatientRequest(PatientRequest $patient_request, Request $request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/solicitação atualizar');
        return $patientRequestService->updatePatientRequest($patient_request, $request);
    }

    public function deletePatientRequest(PatientRequest $patient_request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/solicitação deletar');
        return $patientRequestService->deletePatientRequest($patient_request);
    }

    public function getPatientRequestAttachments(PatientRequest $patient_request)
    {
        $this->authorize('tfd/solicitação anexos');
        $attachments = $patient_request->attachments()
            ->orderBy('id','asc')
            ->get();
        return response()->json($attachments, 200);
    }

    public function createPatientRequestAttachment(PatientRequest $patient_request, Request $request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/solicitação anexos');
        return $patientRequestService->createPatientRequestAttachment($patient_request, $request);
    }

    public function updatePatientRequestAttachment(PatientRequestAttachment $patient_request_attachment, Request $request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/solicitação anexos');
        return $patientRequestService->updatePatientRequestAttachment($patient_request_attachment, $request);
    }

    public function deletePatientRequestAttachment(PatientRequestAttachment $patient_request_attachment, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/solicitação anexos');
        return $patientRequestService->deletePatientRequestAttachment($patient_request_attachment);
    }

    public function processPatientRequestToMedical(PatientRequest $patient_request, Request $request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/solicitação atualizar');
        return $patientRequestService->processPatientRequestToMedical($patient_request, $request);
    }

    public function movePatientRequestFromProcesses(PatientRequest $patient_request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/solicitação atualizar');
        return $patientRequestService->movePatientRequestFromProcesses($patient_request);
    }

    public function movePatientRequestFromOthers(PatientRequest $patient_request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/solicitação atualizar');
        return $patientRequestService->movePatientRequestFromOthers($patient_request);
    }

    public function finishBackPatientRequest(PatientRequest $patient_request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/solicitação atualizar');
        return $patientRequestService->finishBackPatientRequest($patient_request);
    }
    
}
