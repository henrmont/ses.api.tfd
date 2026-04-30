<?php

namespace App\Http\Controllers;

use App\Models\HospitalUnity;
use App\Services\HospitalUnityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class HospitalUnityController extends Controller
{
    use AuthorizesRequests;

    public function getHospitalUnities()
    {
        $this->authorize('tfd/unidade hospitalar listar');
        $hospital_unities = HospitalUnity::query()
            ->orderBy('id','desc')
            ->get();
        return response()->json($hospital_unities, 200);
    }

    public function createHospitalUnity(Request $request, HospitalUnityService $hospitalUnityService)
    {
        $this->authorize('tfd/unidade hospitalar criar');
        return $hospitalUnityService->createHospitalUnity($request);
    }

    public function updateHospitalUnity(HospitalUnity $hospital_unity, Request $request, HospitalUnityService $hospitalUnityService)
    {
        $this->authorize('tfd/unidade hospitalar atualizar');
        return $hospitalUnityService->updateHospitalUnity($hospital_unity, $request);
    }

    public function deleteHospitalUnity(HospitalUnity $hospital_unity, HospitalUnityService $hospitalUnityService)
    {
        $this->authorize('tfd/unidade hospitalar deletar');
        return $hospitalUnityService->deleteHospitalUnity($hospital_unity);
    }
}
