<?php

namespace App\Events;

use App\Models\PatientCare;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// ShouldBroadcastNow envia o evento imediatamente sem precisar de fila (queue)
class PatientEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $patientCare;

    public function __construct(PatientCare $patientCare)
    {
        // Formata os dados no mesmo padrão retornado pela sua API
        $this->patientCare = [
            'id' => $patientCare->id,
            'status' => $patientCare->status,
            'patient' => [
                'id' => $patientCare->patient->id,
                'name' => $patientCare->patient->name,
                'document' => $patientCare->patient->document,
                'cns' => $patientCare->patient->cns,
            ]
        ];
    }

    // Nome do canal público onde o frontend vai se inscrever
    public function broadcastOn(): array
    {
        return [
            new Channel('patients-channel')
        ];
    }

    // Nome personalizado do evento recebido pelo Pusher no frontend
    public function broadcastAs(): string
    {
        return 'patient.updated';
    }
}