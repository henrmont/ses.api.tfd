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
            ->notPatientBack()
            ->whereNull('back_to_cost_assistance')
            ->where(function ($query) {
                $query->whereNull('back_to_owner')->orWhereNull('back_from_travel');
            })
            ->where(function ($query) {
                $query->whereNull('back_to_medical')->orWhereNull('back_from_travel');
            })
            ->where(function ($query) {
                $query->whereNull('back_to_social')->orWhereNull('back_from_travel');
            })
            ->where('is_travel_archived', false)
            ->where('type','Agendamento')
            ->with('report.patientCare.patient','report.patientCare.user.professional','report.cid','report.attachments','attachments','hospitalUnity','medicalProfessional','ownerProfessional','socialProfessional','travelProfessional','costAssistanceProfessional','accountabilityProfessional','travels.passengers.patient','travels.passengers.escort','costAssistances.costAssistanceDailies.dailyCost', 'accountabilities.accountabilityDailies.dailyCost')
            ->orderBy('id','desc')
            ->get();
        return response()->json($patient_requests, 200);
    }

    public function getArchivePatientRequests()
    {
        $this->authorize('tfd/passagem listar');
        $patient_requests = PatientRequest::query()
            ->notPatientBack()
            ->where(function ($query) {
                $query->whereNull('back_to_owner')->orWhereNull('back_from_travel');
            })
            ->where(function ($query) {
                $query->whereNull('back_to_medical')->orWhereNull('back_from_travel');
            })
            ->where(function ($query) {
                $query->whereNull('back_to_social')->orWhereNull('back_from_travel');
            })
            ->where('is_travel_archived', true)
            ->with('report.patientCare.patient','report.patientCare.user.professional','report.cid','report.attachments','attachments','hospitalUnity','medicalProfessional','ownerProfessional','socialProfessional','travelProfessional','costAssistanceProfessional','accountabilityProfessional','travels.passengers.patient','travels.passengers.escort','costAssistances.costAssistanceDailies.dailyCost', 'accountabilities.accountabilityDailies.dailyCost')
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
        return $patientRequestService->undoPatientRequest($patient_request, $request, 'travel');
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

    public function movePatientRequestFromArchive(PatientRequest $patient_request, TravelService $travelService)
    {
        $this->authorize('tfd/passagem atualizar');
        return $travelService->movePatientRequestFromArchive($patient_request);
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
            ->with('travel')
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

    public function archivePatientRequest(PatientRequest $patient_request, PatientRequestService $patientRequestService)
    {
        $this->authorize('tfd/passagem atualizar');
        return $patientRequestService->archiveTravelPatientRequest($patient_request);
    }

    public function finishBackPatientRequest(PatientRequest $patient_request, TravelService $travelService)
    {
        $this->authorize('tfd/passagem atualizar');
        return $travelService->finishBackPatientRequest($patient_request);
    }

    // validators
    public function passengerExists(Travel $travel, Request $request)
    {
        $this->authorize('tfd/passagem listar');
        // Converter para boolean para evitar erros de falsy (ex: string "true"/"false")
        $isPatient = filter_var($request->is_patient, FILTER_VALIDATE_BOOLEAN);

        $exists = $travel->passengers()
            ->where(function ($query) use ($request, $isPatient) {
                if ($isPatient) {
                    $query->where('patient_id', $request->passenger_id);
                } else {
                    $query->where('escort_id', $request->passenger_id);
                }
            })
            ->exists();
        return response()->json($exists, 200);
    }
}
