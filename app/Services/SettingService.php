<?php

namespace App\Services;

use App\Models\DailyCost;
use Exception;
use Illuminate\Http\Request;

class SettingService
{
    public function updateDailyCost(DailyCost $daily_cost, Request $request)
    {
        try {
            $daily_cost->update($request->only(['value']));
            return response()->json(['message' => 'Diária atualizada com sucesso.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}