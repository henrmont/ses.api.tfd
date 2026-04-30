<?php

namespace App\Http\Controllers;

use App\Models\PatientCare;
use App\Models\PatientRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use AuthorizesRequests;

    public function getPatients()
    {
        $this->authorize('tfd/consultar paciente');
        $patient_cares = PatientCare::query()
            ->tfd()
            ->with('patient.patientInfo','user.professional')
            ->orderBy('id','desc')
            ->get();
        return response()->json($patient_cares, 200);
    }

    public function getArchivedPatients()
    {
        $this->authorize('tfd/consultar arquivo');
        $patient_cares = PatientCare::query()
            ->tfd()
            ->where('is_archived', true)
            ->with('patient.patientInfo','user.professional')
            ->orderBy('id','desc')
            ->get();
        return response()->json($patient_cares, 200);
    }

    public function getArchivedPatientRequests()
    {
        $this->authorize('tfd/consultar arquivo');
        $patient_requests = PatientRequest::query()
            ->where('is_archived', true)
            ->with('report.patientCare.patient','report.cid','report.attachments','attachments','hospitalUnity','medicalProfessional','ownerProfessional','socialProfessional','travelProfessional','costAssistanceProfessional','accountabilityProfessional','paymentProfessional','travels.passengers.patient','travels.passengers.escort','costAssistances.costAssistanceDailies.dailyCost', 'accountabilities.accountabilityDailies.dailyCost', 'paymentInfo')
            ->orderBy('id','desc')
            ->get();
        return response()->json($patient_requests, 200);
    }
}
