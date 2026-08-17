<?php

namespace App\Http\Controllers;

use App\Models\BudgetAllocation;
use App\Models\DailyCost;
use App\Services\SettingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected SettingService $settingService
    ) {}

    /**
     * Listar todos os custos de diárias.
     */
    public function getDailyCosts(): JsonResponse
    {
        $this->authorize('tfd/configuração listar');

        $daily_costs = DailyCost::query()
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($daily_costs, JsonResponse::HTTP_OK);
    }

    /**
     * Atualizar valor de um custo de diária específico.
     */
    public function updateDailyCost(DailyCost $daily_cost, Request $request): JsonResponse
    {
        $this->authorize('tfd/configuração atualizar');

        return $this->settingService->updateDailyCost($daily_cost, $request);
    }

    /**
     * Obter a alocação orçamentária atual.
     */
    public function getBudgetAllocation(): JsonResponse
    {
        $this->authorize('tfd/configuração listar');

        $budget_allocation = BudgetAllocation::query()
            ->orderBy('id', 'asc')
            ->first();

        return response()->json($budget_allocation, JsonResponse::HTTP_OK);
    }

    /**
     * Atualizar dados da alocação orçamentária.
     */
    public function updateBudgetAllocation(BudgetAllocation $budget_allocation, Request $request): JsonResponse
    {
        $this->authorize('tfd/configuração atualizar');

        return $this->settingService->updateBudgetAllocation($budget_allocation, $request);
    }
}