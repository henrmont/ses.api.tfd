<?php

namespace App\Http\Controllers;

use App\Models\Passenger;
use App\Models\Patient;
use App\Models\PatientCare;
use App\Models\PatientRequest;
use App\Models\Travel;
use App\Models\TravelRoute;
use App\Services\PatientRequestService;
use App\Services\TravelService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class TravelController extends Controller
{
    use AuthorizesRequests;
    
    public function getPatientRequests()
    {
        $this->authorize('tfd/passagem listar');
        $patient_requests = PatientRequest::query()
            ->whereNull('back_to_owner')
            ->whereNull('back_to_medical')
            ->whereNull('back_to_social')
            ->where('is_archived', false)
            ->where('type','Agendamento')
            ->with('report.patientCare.patient','report.cid','report.attachments','attachments','hospitalUnity','medicalProfessional','ownerProfessional','socialProfessional','travelProfessional','costAssistanceProfessional','accountabilityProfessional','paymentProfessional','travels.passengers.patient','travels.passengers.escort','costAssistances.costAssistanceDailies.dailyCost', 'accountabilities.accountabilityDailies.dailyCost', 'paymentInfo','paymentAttachments')
            ->orderBy('id','desc')
            ->get();
        return response()->json($patient_requests, 200);
    }

    public function haltedPatientRequest(PatientRequest $patient_request, TravelService $travelService)
    {
        $this->authorize('tfd/passagem atualizar');
        return $travelService->haltedPatientRequest($patient_request);
    }

    public function getPatientEscorts(PatientCare $patient_care)
    {
        $this->authorize('tfd/passagem acompanhantes');
        $patient_escorts = $patient_care->escorts()
            ->orderBy('id','asc')
            ->get();
        return response()->json($patient_escorts, 200);
    }

    public function undoPatientRequest(PatientRequest $patient_request, Request $request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/passagem atualizar');
        return $patientRequestService->undoPatientRequest($patient_request, $request);
    }

    public function finishPatientRequestTravel(PatientRequest $patient_request, TravelService $travelService)
    {
        $this->authorize('tfd/passagem atualizar');
        return $travelService->finishPatientRequestTravel($patient_request);  
    }

    public function movePatientRequestFromFinished(PatientRequest $patient_request, TravelService $travelService)
    {
        $this->authorize('tfd/passagem atualizar');
        return $travelService->movePatientRequestFromFinished($patient_request);
    }

    public function movePatientRequestFromOthers(PatientRequest $patient_request, TravelService $travelService)
    {
        $this->authorize('tfd/passagem atualizar');
        return $travelService->movePatientRequestFromOthers($patient_request);
    }

    public function importTravels(Request $request, TravelService $travelService)
    {
        $this->authorize('tfd/passagem importar');
        return $travelService->importTravels($request);
    }

    public function getTravels(PatientRequest $patient_request)
    {
        $this->authorize('tfd/passagem listar');
        $travels = $patient_request->travels()
            ->with('patientRequest.report.patientCare.patient','patientRequest.report.patientCare.escorts')
            ->orderBy('id','desc')
            ->get();
        return response()->json($travels, 200);
    }

    public function createTravel(PatientRequest $patient_request, Request $request, TravelService $travelService)
    {
        $this->authorize('tfd/passagem criar');
        return $travelService->createTravel($patient_request, $request);
    }

    public function updateTravel(Travel $travel, Request $request, TravelService $travelService)
    {
        $this->authorize('tfd/passagem atualizar');
        return $travelService->updateTravel($travel, $request);
    }

    public function deleteTravel(Travel $travel, Request $request, TravelService $travelService)
    {
        $this->authorize('tfd/passagem deletar');
        return $travelService->deleteTravel($travel, $request);
    }

    public function getPassengers(Travel $travel)
    {
        $this->authorize('tfd/passagem atualizar');
        $passengers = $travel->passengers()
            ->with('patient','escort')
            ->orderBy('id','asc')
            ->get();
        return response()->json($passengers, 200);
    }

    public function createPassenger(Travel $travel, Request $request, TravelService $travelService)
    {
        $this->authorize('tfd/passagem atualizar');
        return $travelService->createPassenger($travel, $request);
    }

    public function updatePassenger(Passenger $passenger, Request $request, TravelService $travelService)
    {
        $this->authorize('tfd/passagem atualizar');
        return $travelService->updatePassenger($passenger, $request);
    }

    public function deletePassenger(Passenger $passenger, TravelService $travelService)
    {
        $this->authorize('tfd/passagem atualizar');
        return $travelService->deletePassenger($passenger);
    }

    public function getTravelRoutes(Travel $travel)
    {
        $this->authorize('tfd/passagem atualizar');
        $travel_routes = $travel->travelRoutes()
            ->orderBy('id','asc')
            ->get();
        return response()->json($travel_routes, 200);
    }

    public function createTravelRoute(Travel $travel, Request $request, TravelService $travelService)
    {
        $this->authorize('tfd/passagem atualizar');
        return $travelService->createTravelRoute($travel, $request);
    }

    public function updateTravelRoute(TravelRoute $travel_route, Request $request, TravelService $travelService)
    {
        $this->authorize('tfd/passagem atualizar');
        return $travelService->updateTravelRoute($travel_route, $request);
    }

    public function deleteTravelRoute(TravelRoute $travel_route, TravelService $travelService)
    {
        $this->authorize('tfd/passagem atualizar');
        return $travelService->deleteTravelRoute($travel_route);
    }
}
