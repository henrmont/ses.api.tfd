<?php

namespace App\Services;

use App\Models\Escort;
use App\Models\Passenger;
use App\Models\Patient;
use App\Models\PatientRequest;
use App\Models\Professional;
use App\Models\Ticket;
use App\Models\Travel;
use App\Models\TravelRoute;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TravelService
{
    private function tfd()
    {
        return DB::connection(); 
    }

    public function haltedPatientRequest(PatientRequest $patient_request)
    {
        try {
            $patient_request->update(['is_travel_bookmark' => !$patient_request->is_travel_bookmark]);
            return response()->json(['message' => $patient_request->is_travel_bookmark ? 'Solicitação marcada em sobrestado.' : 'Solicitação desmarcada em sobrestado.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function finishPatientRequestTravel(PatientRequest $patient_request)
    {
        try {
            $patient_request->update(['is_travel_finished' => true]);
            return response()->json(['message' => 'Solicitação de viagem finalizada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function movePatientRequestFromFinished(PatientRequest $patient_request)
    {
        try {
            $patient_request->update([
                'is_travel_finished' => false,
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
                'travel_professional_id' => Professional::where('user_id',auth()->user()->id)->first()->id,
            ]);
            return response()->json(['message' => 'Solicitação transferida com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function importTravels(Request $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $response = Http::timeout(300)->get('https://www.westonline.tur.br/tfdWS/TFD/vendas', [
                'user' => 'tfdws',
                'pwd' => '%4jK*M@jS',
                'dtEmissao' => date('d/m/Y', strtotime($request->import_at)),
            ]);
            $xmlString = $response->body();
            $xmlObject = simplexml_load_string($xmlString);
            $json = json_encode($xmlObject);
            $array = json_decode($json, true);
            foreach ($array as $item) {
                $lastOS = null;
                foreach ($item as $reservation) {
                    $travel = Travel::where('os', $reservation['numeroOs'])->first();
                    if ($travel) {
                        if ($lastOS != $reservation['numeroOs']) {
                            $travel->update([
                                'origin' => $reservation['origem'],
                                'destination' => $reservation['destino'],
                                'type' => $reservation['tipo'],
                                'locator' => $reservation['localizador'],
                                'departure_date' => date('Y-m-d', strtotime($reservation['dataEmbarque'])),
                                // 'return_date' => date('Y-m-d', strtotime($reservation['dataDesembarque'])),
                                'description' => $reservation['descricaoOs']
                            ]);
                            foreach ($reservation['trechos']['trecho'] as $router) {
                                $travel->travelRoutes()->create([
                                    'origin' => $router['dsOrigem'],
                                    'destination' => $router['dsDestino'],
                                ]);
                            }
                        }
                        $lastOS = $reservation['numeroOs'];
                        if ($reservation['passageiros']['passageiro']['isAcompanhante'] == 'true') {
                            $travel->passengers()->create([
                                'is_patient' => false,
                                'escort_id' => Escort::where('document', $reservation['passageiros']['passageiro']['cpf'])->first()->id,
                                'tariff' => $reservation['passageiros']['passageiro']['tarifa'],
                                'tax' => $reservation['passageiros']['passageiro']['taxas']
                            ]);
                        } else {
                            $travel->passengers()->create([
                                'is_patient' => true,
                                'patient_id' => Patient::where('document', $reservation['passageiros']['passageiro']['cpf'])->first()->id,
                                'tariff' => $reservation['passageiros']['passageiro']['tarifa'],
                                'tax' => $reservation['passageiros']['passageiro']['taxas']
                            ]);
                        }
                    }
                }
            }
            $this->tfd()->commit();
            return response()->json(['message' => 'Passagens importadas com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function createTravel(PatientRequest $patient_request, Request $request)
    {
        try {
            $patient_request->travels()->create($request->all());
            return response()->json(['message' => 'Viagem criada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateTravel(Travel $travel, Request $request)
    {
        try {
            $travel->update($request->all());
            return response()->json(['message' => 'Viagem atualizada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteTravel(Travel $travel)
    {
        try {
            $travel->delete();
            return response()->json(['message' => 'Viagem deletada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function createPassenger(Travel $travel, Request $request)
    {
        try {
            $this->tfd()->beginTransaction();
            $passenger = $travel->passengers()->create($request->only('is_patient','tariff','tax'));
            if ($passenger->is_patient)
                $passenger->update(['patient_id' => $request->passenger]);
            else
                $passenger->update(['escort_id' => $request->passenger]);
            $this->tfd()->commit();
            return response()->json(['message' => 'Passageiro criado com sucesso.'], 200);
        } catch (Exception $e) {
            $this->tfd()->rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updatePassenger(Passenger $passenger, Request $request)
    {
        try {
            $passenger->update($request->only('tariff','tax'));
            return response()->json(['message' => 'Passageiro atualizado com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deletePassenger(Passenger $passenger)
    {
        try {
            $passenger->delete();
            return response()->json(['message' => 'Passageiro deletado com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function createTravelRoute(Travel $travel, Request $request)
    {
        try {
            $travel->travelRoutes()->create($request->all());
            return response()->json(['message' => 'Rota criada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateTravelRoute(TravelRoute $travel_route, Request $request)
    {
        try {
            $travel_route->update($request->all());
            return response()->json(['message' => 'Rota atualizada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteTravelRoute(TravelRoute $travel_route)
    {
        try {
            $travel_route->delete();
            return response()->json(['message' => 'Rota deletada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

}