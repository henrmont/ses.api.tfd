<?php

namespace App\Services;

use App\Models\BudgetAllocation;
use App\Models\DailyCost;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SettingService
{
    /**
     * Atualizar o valor de um custo de diária específico.
     */
    public function updateDailyCost(DailyCost $daily_cost, Request $request): JsonResponse
    {
        try {
            $daily_cost->update($request->only(['value']));

            return response()->json(['message' => 'Diária atualizada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao atualizar custo de diária: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar os dados da alocação orçamentária.
     */
    public function updateBudgetAllocation(BudgetAllocation $budget_allocation, Request $request): JsonResponse
    {
        try {
            $budget_allocation->update($request->only([
                'program',
                'active_project',
                'nature_of_expenditure',
                'source',
            ]));

            return response()->json(['message' => 'Alocação orçamentária atualizada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao atualizar alocação orçamentária: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}