<?php

namespace App\Http\Controllers;

use App\Models\Opinion;
use App\Models\PatientRequest;
use App\Models\Professional;
use App\Models\Report;
use App\Services\OpinionService;
use App\Services\PatientRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class OpinionController extends Controller
{
    use AuthorizesRequests;

    public function getPatientRequests()
    {
        $this->authorize('tfd/parecer listar');
        $patient_requests = PatientRequest::query()
            ->whereNull('back_to_owner')
            ->where('is_archived', false)
            ->with('report.patientCare.patient','report.cid','report.attachments','attachments','hospitalUnity','medicalProfessional','ownerProfessional','socialProfessional','travelProfessional','costAssistanceProfessional','accountabilityProfessional','paymentProfessional','travels.passengers.patient','travels.passengers.escort','costAssistances.costAssistanceDailies.dailyCost', 'accountabilities.accountabilityDailies.dailyCost', 'paymentInfo','paymentAttachments')
            ->orderBy('id','desc')
            ->get();
        return response()->json($patient_requests, 200);
    }

    public function getType()
    {
        $this->authorize('tfd/parecer listar');
        $type = Professional::query()
            ->where('user_id',auth()->user()->id)
            ->first()->type;
        return response()->json($type, 200);
    }

    public function getSocialProfessionals()
    {
        $this->authorize('tfd/parecer listar');
        $social_profissionals = Professional::query()
            ->with('user')
            ->withCount('patientSocialRequests')
            ->where('type','Assistente Social')
            ->get();
        return response()->json($social_profissionals, 200);
    }

    public function getOpinions(PatientRequest $patient_request)
    {
        $this->authorize('tfd/parecer listar');
        $opinions = $patient_request->opinions()
            ->with('patientRequest.report.patientCare','professional')
            ->orderBy('id','asc')
            ->get();
        return response()->json($opinions, 200);
    }

    public function createOpinion(PatientRequest $patient_request, Request $request, OpinionService $opinionService)
    {
        $this->authorize('tfd/parecer criar');
        return $opinionService->createOpinion($patient_request, $request);
    }

    public function updateOpinion(Opinion $opinion, Request $request, OpinionService $opinionService)
    {
        $this->authorize('tfd/parecer atualizar');
        return $opinionService->updateOpinion($opinion, $request);
    }

    public function deleteOpinion(Opinion $opinion, OpinionService $opinionService)
    {
        $this->authorize('tfd/parecer deletar');
        return $opinionService->deleteOpinion($opinion);
    }

    public function processPatientRequestToSocial(PatientRequest $patient_request, Request $request, OpinionService $opinionService)
    {
        $this->authorize('tfd/parecer atualizar');
        return $opinionService->processPatientRequestToSocial($patient_request, $request);
    }

    public function undoPatientRequest(PatientRequest $patient_request, Request $request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/parecer atualizar');
        return $patientRequestService->undoPatientRequest($patient_request, $request);
    }

    public function archivePatientRequest(PatientRequest $patient_request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/parecer atualizar');
        return $patientRequestService->archivePatientRequest($patient_request);
    }

    public function haltedPatientRequest($type, PatientRequest $patient_request, OpinionService $opinionService)
    {
        $this->authorize('tfd/parecer atualizar');
        return $opinionService->haltedPatientRequest($type, $patient_request);
    }

    public function movePatientRequestFromProcesses($type, PatientRequest $patient_request, OpinionService $opinionService)
    {
        $this->authorize('tfd/parecer atualizar');
        return $opinionService->movePatientRequestFromProcesses($type, $patient_request);
    }

    public function movePatientRequestFromOthers($type, PatientRequest $patient_request, OpinionService $opinionService)
    {
        $this->authorize('tfd/parecer atualizar');
        return $opinionService->movePatientRequestFromOthers($type, $patient_request);
    }

    public function getHistoryPatientRequests(Report $report, PatientRequest $patient_request)
    {
        $this->authorize('tfd/parecer listar');
        $patient_requests = $report->patientRequests()
            ->whereNot('id', $patient_request->id)
            ->with('report.patientCare.patient','report.cid','report.attachments','attachments','hospitalUnity','medicalProfessional','ownerProfessional','socialProfessional','travelProfessional','costAssistanceProfessional','accountabilityProfessional','paymentProfessional','travels.passengers.patient','travels.passengers.escort','costAssistances.costAssistanceDailies.dailyCost', 'accountabilities.accountabilityDailies.dailyCost', 'paymentInfo','paymentAttachments')
            ->orderBy('id','asc')
            ->get();
        return response()->json($patient_requests, 200);
    }

    public function getCostAssistanceProfessionals()
    {
        $this->authorize('tfd/parecer listar');
        $cost_assistance_profissionals = Professional::query()
            ->with('user')
            ->withCount('patientCostAssistanceRequests')
            ->where('type','Ajuda de Custo')
            ->get();
        return response()->json($cost_assistance_profissionals, 200);
    }

    public function getTravelProfessionals()
    {
        $this->authorize('tfd/parecer listar');
        $travel_profissionals = Professional::query()
            ->with('user')
            ->withCount('patientTravelRequests')
            ->where('type','Passagem')
            ->get();
        return response()->json($travel_profissionals, 200);
    }

    public function processPatientRequestToCostAssistanceAndTravel(PatientRequest $patient_request, Request $request, OpinionService $opinionService)
    {
        $this->authorize('tfd/parecer atualizar');
        return $opinionService->processPatientRequestToCostAssistanceAndTravel($patient_request, $request);
    }

}
