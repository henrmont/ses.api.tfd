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
     * Listar os custos de diárias.
     * GET /tfd/settings/daily-costs
     */
    public function getDailyCosts(): JsonResponse
    {
        $this->authorize('tfd/configuração listar');

        $dailyCosts = DailyCost::query()
            ->orderBy('id')
            ->get();

        return response()->json($dailyCosts, JsonResponse::HTTP_OK);
    }

    /**
     * Atualizar o valor de um custo de diária.
     * PATCH /tfd/settings/daily-costs/{daily_cost}
     */
    public function updateDailyCost(DailyCost $daily_cost, Request $request): JsonResponse
    {
        $this->authorize('tfd/configuração atualizar');

        return $this->settingService->updateDailyCost($daily_cost, $request);
    }

    /**
     * Retornar a alocação orçamentária vigente.
     * GET /tfd/settings/budget-allocation
     */
    public function getBudgetAllocation(): JsonResponse
    {
        $this->authorize('tfd/configuração listar');

        $budgetAllocation = BudgetAllocation::query()->first();

        return response()->json($budgetAllocation, JsonResponse::HTTP_OK);
    }

    /**
     * Atualizar a alocação orçamentária.
     * PATCH /tfd/settings/budget-allocation/{budget_allocation}
     */
    public function updateBudgetAllocation(BudgetAllocation $budget_allocation, Request $request): JsonResponse
    {
        $this->authorize('tfd/configuração atualizar');

        return $this->settingService->updateBudgetAllocation($budget_allocation, $request);
    }
}