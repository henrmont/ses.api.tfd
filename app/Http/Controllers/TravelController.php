<?php

namespace App\Http\Controllers;

use App\Models\Passenger;
use App\Models\PatientCare;
use App\Models\PatientRequest;
use App\Models\Travel;
use App\Models\TravelRoute;
use App\Services\PatientRequestService;
use App\Services\TravelService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TravelController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected TravelService $travelService,
        protected PatientRequestService $patientRequestService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Gestão de Solicitações do TFD (Passagens)
    |--------------------------------------------------------------------------
    */

    /**
     * Listar solicitações de passagem pendentes.
     */
    public function getPatientRequests(): JsonResponse
    {
        $this->authorize('tfd/passagem listar');

        $patientRequests = PatientRequest::query()
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
            ->where('type', 'Agendamento')
            ->with([
                'report.patientCare.patient',
                'report.patientCare.user.professional',
                'report.cid',
                'report.attachments',
                'attachments',
                'hospitalUnity',
                'medicalProfessional',
                'ownerProfessional',
                'socialProfessional',
                'travelProfessional',
                'costAssistanceProfessional',
                'accountabilityProfessional',
                'travels.passengers.patient',
                'travels.passengers.escort',
                'costAssistances.costAssistanceDailies.dailyCost',
                'accountabilities.accountabilityDailies.dailyCost',
            ])
            ->latest('id')
            ->get();

        return response()->json($patientRequests, JsonResponse::HTTP_OK);
    }

    /**
     * Listar solicitações arquivadas no setor de passagens.
     */
    public function getArchivePatientRequests(): JsonResponse
    {
        $this->authorize('tfd/passagem listar');

        $patientRequests = PatientRequest::query()
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
            ->with([
                'report.patientCare.patient',
                'report.patientCare.user.professional',
                'report.cid',
                'report.attachments',
                'attachments',
                'hospitalUnity',
                'medicalProfessional',
                'ownerProfessional',
                'socialProfessional',
                'travelProfessional',
                'costAssistanceProfessional',
                'accountabilityProfessional',
                'travels.passengers.patient',
                'travels.passengers.escort',
                'costAssistances.costAssistanceDailies.dailyCost',
                'accountabilities.accountabilityDailies.dailyCost',
            ])
            ->latest('id')
            ->get();

        return response()->json($patientRequests, JsonResponse::HTTP_OK);
    }

    /**
     * Listar acompanhantes do paciente vinculado ao atendimento.
     */
    public function getPatientEscorts(PatientCare $patient_care): JsonResponse
    {
        $this->authorize('tfd/passagem acompanhantes');

        $patientEscorts = $patient_care->escorts()
            ->oldest('id')
            ->get();

        return response()->json($patientEscorts, JsonResponse::HTTP_OK);
    }

    /*
    |--------------------------------------------------------------------------
    | Tramitação e Mudança de Estados da Solicitação
    |--------------------------------------------------------------------------
    */

    /**
     * Sobrestar/paralisar a solicitação de passagem.
     */
    public function haltedPatientRequest(PatientRequest $patient_request)
    {
        $this->authorize('tfd/passagem atualizar');

        return $this->travelService->haltedPatientRequest($patient_request);
    }

    /**
     * Desfazer ação / devolver solicitação no fluxo de passagens.
     */
    public function undoPatientRequest(PatientRequest $patient_request, Request $request)
    {
        $this->authorize('tfd/passagem atualizar');

        return $this->patientRequestService->undoPatientRequest($patient_request, $request, 'travel');
    }

    /**
     * Mover solicitação a partir do arquivo.
     */
    public function movePatientRequestFromArchive(PatientRequest $patient_request)
    {
        $this->authorize('tfd/passagem atualizar');

        return $this->travelService->movePatientRequestFromArchive($patient_request);
    }

    /**
     * Mover solicitação a partir do setor "Outros".
     */
    public function movePatientRequestFromOthers(PatientRequest $patient_request)
    {
        $this->authorize('tfd/passagem atualizar');

        return $this->travelService->movePatientRequestFromOthers($patient_request);
    }

    /**
     * Arquivar a solicitação de passagem.
     */
    public function archivePatientRequest(PatientRequest $patient_request)
    {
        $this->authorize('tfd/passagem atualizar');

        return $this->travelService->archiveTravelPatientRequest($patient_request);
    }

    /**
     * Finalizar devolução da solicitação de passagem.
     */
    public function finishBackPatientRequest(PatientRequest $patient_request)
    {
        $this->authorize('tfd/passagem atualizar');

        return $this->travelService->finishBackPatientRequest($patient_request);
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Viagens (Travels)
    |--------------------------------------------------------------------------
    */

    /**
     * Listar viagens vinculadas a uma solicitação.
     */
    public function getTravels(PatientRequest $patient_request): JsonResponse
    {
        $this->authorize('tfd/passagem listar');

        $travels = $patient_request->travels()
            ->with([
                'patientRequest.report.patientCare.patient',
                'patientRequest.report.patientCare.escorts',
            ])
            ->latest('id')
            ->get();

        return response()->json($travels, JsonResponse::HTTP_OK);
    }

    /**
     * Importar viagens via arquivo/requisição.
     */
    public function importTravels(Request $request)
    {
        $this->authorize('tfd/passagem importar');

        return $this->travelService->importTravels($request);
    }

    /**
     * Cadastrar nova viagem.
     */
    public function createTravel(PatientRequest $patient_request, Request $request)
    {
        $this->authorize('tfd/passagem criar');

        return $this->travelService->createTravel($patient_request, $request);
    }

    /**
     * Atualizar dados da viagem.
     */
    public function updateTravel(Travel $travel, Request $request)
    {
        $this->authorize('tfd/passagem atualizar');

        return $this->travelService->updateTravel($travel, $request);
    }

    /**
     * Excluir uma viagem.
     */
    public function deleteTravel(Travel $travel, Request $request)
    {
        $this->authorize('tfd/passagem deletar');

        return $this->travelService->deleteTravel($travel, $request);
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Passageiros (Passengers)
    |--------------------------------------------------------------------------
    */

    /**
     * Listar passageiros vinculados a uma viagem.
     */
    public function getPassengers(Travel $travel): JsonResponse
    {
        $this->authorize('tfd/passagem atualizar');

        $passengers = $travel->passengers()
            ->with('patient', 'escort')
            ->oldest('id')
            ->get();

        return response()->json($passengers, JsonResponse::HTTP_OK);
    }

    /**
     * Adicionar passageiro à viagem.
     */
    public function createPassenger(Travel $travel, Request $request)
    {
        $this->authorize('tfd/passagem atualizar');

        return $this->travelService->createPassenger($travel, $request);
    }

    /**
     * Atualizar dados do passageiro.
     */
    public function updatePassenger(Passenger $passenger, Request $request)
    {
        $this->authorize('tfd/passagem atualizar');

        return $this->travelService->updatePassenger($passenger, $request);
    }

    /**
     * Remover passageiro da viagem.
     */
    public function deletePassenger(Passenger $passenger)
    {
        $this->authorize('tfd/passagem atualizar');

        return $this->travelService->deletePassenger($passenger);
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Rotas/Trechos de Viagem (TravelRoutes)
    |--------------------------------------------------------------------------
    */

    /**
     * Listar rotas/trechos de uma viagem.
     */
    public function getTravelRoutes(Travel $travel): JsonResponse
    {
        $this->authorize('tfd/passagem atualizar');

        $travelRoutes = $travel->travelRoutes()
            ->with('travel')
            ->oldest('id')
            ->get();

        return response()->json($travelRoutes, JsonResponse::HTTP_OK);
    }

    /**
     * Adicionar rota/trecho à viagem.
     */
    public function createTravelRoute(Travel $travel, Request $request)
    {
        $this->authorize('tfd/passagem atualizar');

        return $this->travelService->createTravelRoute($travel, $request);
    }

    /**
     * Atualizar rota/trecho da viagem.
     */
    public function updateTravelRoute(TravelRoute $travel_route, Request $request)
    {
        $this->authorize('tfd/passagem atualizar');

        return $this->travelService->updateTravelRoute($travel_route, $request);
    }

    /**
     * Excluir rota/trecho da viagem.
     */
    public function deleteTravelRoute(TravelRoute $travel_route)
    {
        $this->authorize('tfd/passagem atualizar');

        return $this->travelService->deleteTravelRoute($travel_route);
    }

    /*
    |--------------------------------------------------------------------------
    | Validações Auxiliares
    |--------------------------------------------------------------------------
    */

    /**
     * Verificar se o passageiro já está cadastrado na viagem.
     */
    public function passengerExists(Travel $travel, Request $request): JsonResponse
    {
        $this->authorize('tfd/passagem listar');

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

        return response()->json($exists, JsonResponse::HTTP_OK);
    }
}