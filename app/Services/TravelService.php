<?php

namespace App\Services;

use App\Models\Escort;
use App\Models\Passenger;
use App\Models\Patient;
use App\Models\PatientRequest;
use App\Models\Professional;
use App\Models\Travel;
use App\Models\TravelRoute;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TravelService
{
    /**
     * Conexão com o banco padrão (TFD).
     */
    private function tfd(): Connection
    {
        return DB::connection();
    }

    /*
    |--------------------------------------------------------------------------
    | Tramitação e Mudança de Estados da Solicitação
    |--------------------------------------------------------------------------
    */

    /**
     * Alternar marcação de sobrestado/paralisação da viagem.
     */
    public function haltedPatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->update(['is_travel_bookmark' => !$patient_request->is_travel_bookmark]);

            $this->tfd()->commit();

            $message = $patient_request->is_travel_bookmark
                ? 'Solicitação marcada em sobrestado.'
                : 'Solicitação desmarcada em sobrestado.';

            return response()->json(['message' => $message], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao alternar sobrestado da viagem: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Restaurar solicitação arquivada.
     */
    public function movePatientRequestFromArchive(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->update([
                'back_to_travel' => 'Retirou do arquivo',
                'is_travel_archived' => false,
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação retirada do arquivo.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao mover solicitação do arquivo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Transferir solicitação a partir do setor "Outros".
     */
    public function movePatientRequestFromOthers(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $professionalId = Professional::where('user_id', auth()->id())->value('id');

            $patient_request->update([
                'travel_professional_id' => $professionalId,
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação transferida com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao mover solicitação de outros: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Finalizar devolução/retorno atribuído à viagem.
     */
    public function finishBackPatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->update(['back_to_travel' => null]);

            $professionalId = Professional::where('user_id', auth()->id())->value('id');

            if ($patient_request->back_from_travel == $professionalId) {
                $patient_request->update(['back_from_travel' => null]);
            }

            if ($patient_request->back_from_cost_assistance == $professionalId) {
                $patient_request->update(['back_from_cost_assistance' => null]);
            }

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação atualizada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao finalizar retorno da viagem: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Arquivar etapa de viagem da solicitação.
     */
    public function archiveTravelPatientRequest(PatientRequest $patient_request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->update(['is_travel_archived' => true]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação arquivada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao arquivar viagem da solicitação: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Integração e Importação de Passagens
    |--------------------------------------------------------------------------
    */

    /**
     * Importar passagens/viagens via Webservice externo.
     */
    public function importTravels(Request $request): JsonResponse
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
            $array = json_decode(json_encode($xmlObject), true);

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
                                'description' => $reservation['descricaoOs'],
                                'company' => $reservation['cia'],
                            ]);

                            foreach ($reservation['trechos']['trecho'] as $router) {
                                $travel->travelRoutes()->create([
                                    'departure' => $router['dataPartida'],
                                    'arrival' => $router['dataChegada'],
                                    'origin' => $router['dsOrigem'],
                                    'destination' => $router['dsDestino'],
                                    'family' => $router['familia'],
                                ]);
                            }
                        }

                        $lastOS = $reservation['numeroOs'];
                        $passengerData = $reservation['passageiros']['passageiro'];
                        $isEscort = $passengerData['isAcompanhante'] === 'true';

                        if ($isEscort) {
                            $escortId = Escort::where('document', $passengerData['cpf'])->value('id');

                            $travel->passengers()->create([
                                'is_patient' => false,
                                'escort_id' => $escortId,
                                'tariff' => $passengerData['tarifa'],
                                'tax' => $passengerData['taxas'],
                                'type' => $passengerData['tipo'],
                                'gender' => $passengerData['sexo'],
                                'ticket' => $passengerData['numeroDoBilhete'],
                                'discount' => $passengerData['du'],
                            ]);
                        } else {
                            $patientId = Patient::where('document', $passengerData['cpf'])->value('id');

                            $travel->passengers()->create([
                                'is_patient' => true,
                                'patient_id' => $patientId,
                                'tariff' => $passengerData['tarifa'],
                                'tax' => $passengerData['taxas'],
                                'type' => $passengerData['tipo'],
                                'gender' => $passengerData['sexo'],
                                'ticket' => $passengerData['numeroDoBilhete'],
                                'discount' => $passengerData['du'],
                            ]);
                        }
                    }
                }
            }

            $this->tfd()->commit();

            return response()->json(['message' => 'Passagens importadas com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao importar passagens: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Viagens (Travels)
    |--------------------------------------------------------------------------
    */

    /**
     * Criar registro de viagem.
     */
    public function createTravel(PatientRequest $patient_request, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $patient_request->travels()->create($request->all());

            $this->tfd()->commit();

            return response()->json(['message' => 'Viagem criada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao criar viagem: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar dados da viagem.
     */
    public function updateTravel(Travel $travel, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $travel->update($request->all());

            $this->tfd()->commit();

            return response()->json(['message' => 'Viagem atualizada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao atualizar viagem: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Excluir registro de viagem.
     */
    public function deleteTravel(Travel $travel): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $travel->delete();

            $this->tfd()->commit();

            return response()->json(['message' => 'Viagem deletada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao deletar viagem: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Passageiros (Passengers)
    |--------------------------------------------------------------------------
    */

    /**
     * Adicionar passageiro à viagem.
     */
    public function createPassenger(Travel $travel, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $passengerData = $request->only(['is_patient', 'tariff', 'tax', 'type', 'gender', 'seat', 'ticket', 'discount']);

            if (filter_var($request->is_patient, FILTER_VALIDATE_BOOLEAN)) {
                $passengerData['patient_id'] = $request->passenger_id;
            } else {
                $passengerData['escort_id'] = $request->passenger_id;
            }

            $travel->passengers()->create($passengerData);

            $this->tfd()->commit();

            return response()->json(['message' => 'Passageiro criado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao criar passageiro: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar dados do passageiro.
     */
    public function updatePassenger(Passenger $passenger, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $passenger->update($request->only(['tariff', 'tax', 'gender', 'seat', 'ticket', 'discount']));

            $this->tfd()->commit();

            return response()->json(['message' => 'Passageiro atualizado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao atualizar passageiro: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Excluir passageiro da viagem.
     */
    public function deletePassenger(Passenger $passenger): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $passenger->delete();

            $this->tfd()->commit();

            return response()->json(['message' => 'Passageiro deletado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao deletar passageiro: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gestão de Rotas/Trechos de Viagem (TravelRoutes)
    |--------------------------------------------------------------------------
    */

    /**
     * Criar rota/trecho para a viagem.
     */
    public function createTravelRoute(Travel $travel, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $travel->travelRoutes()->create($request->all());

            $this->tfd()->commit();

            return response()->json(['message' => 'Rota criada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao criar rota da viagem: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar rota/trecho da viagem.
     */
    public function updateTravelRoute(TravelRoute $travel_route, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $travel_route->update($request->all());

            $this->tfd()->commit();

            return response()->json(['message' => 'Rota atualizada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao atualizar rota da viagem: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Excluir rota/trecho da viagem.
     */
    public function deleteTravelRoute(TravelRoute $travel_route): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $travel_route->delete();

            $this->tfd()->commit();

            return response()->json(['message' => 'Rota deletada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao deletar rota da viagem: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}