<?php

namespace App\Services;

use App\Models\BudgetAllocation;
use App\Models\DailyCost;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingService
{
    /**
     * Atualizar o valor de um custo de diária específico.
     */
    public function updateDailyCost(DailyCost $daily_cost, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $daily_cost->update([
                'value' => $validated['value'],
            ]);

            return response()->json([
                'message' => 'Diária atualizada com sucesso.',
            ], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao atualizar custo de diária: ' . $e->getMessage(), [
                'daily_cost_id' => $daily_cost->id,
                'trace'         => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erro ao atualizar o custo da diária.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar os dados da alocação orçamentária.
     */
    public function updateBudgetAllocation(BudgetAllocation $budget_allocation, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program'               => ['required', 'string', 'max:255'],
            'active_project'        => ['required', 'string', 'max:255'],
            'nature_of_expenditure' => ['required', 'string', 'max:255'],
            'source'                => ['required', 'string', 'max:255'],
        ]);

        try {
            $budget_allocation->update($validated);

            return response()->json([
                'message' => 'Alocação orçamentária atualizada com sucesso.',
            ], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Erro ao atualizar alocação orçamentária: ' . $e->getMessage(), [
                'budget_allocation_id' => $budget_allocation->id,
                'trace'                => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erro ao atualizar a alocação orçamentária.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}