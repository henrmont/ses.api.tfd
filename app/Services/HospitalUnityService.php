<?php

namespace App\Services;

use App\Models\HospitalUnity;
use Exception;
use Illuminate\Http\Request;

class HospitalUnityService
{
    public function createHospitalUnity(Request $request)
    {
        try {
            HospitalUnity::on('core')->create($request->all());
            return response()->json(['message' => 'Unidade hospitalar criada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateHospitalUnity(HospitalUnity $hospital_unity, Request $request)
    {
        try {
            $hospital_unity->update($request->all());
            return response()->json(['message' => 'Unidade hospitalar atualizada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteHospitalUnity(HospitalUnity $hospital_unity)
    {
        try {
            $hospital_unity->delete();
            return response()->json(['message' => 'Unidade hospitalar excluída com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    
}