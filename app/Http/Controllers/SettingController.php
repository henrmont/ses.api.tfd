<?php

namespace App\Http\Controllers;

use App\Models\BudgetAllocation;
use App\Models\DailyCost;
use App\Services\SettingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use AuthorizesRequests;

    public function getDailyCosts()
    {
        $this->authorize('tfd/configuração listar');
        $daily_costs = DailyCost::query()
            ->orderBy('id', 'asc')
            ->get();
        return response()->json($daily_costs, 200);
    }

    public function updateDailyCost(DailyCost $daily_cost, Request $request, SettingService $settingService)
    {
        $this->authorize('tfd/configuração atualizar');
        return $settingService->updateDailyCost($daily_cost, $request);
    }

    public function getBudgetAllocation()
    {
        $this->authorize('tfd/configuração listar');
        $budget_allocation = BudgetAllocation::query()
            ->orderBy('id', 'asc')
            ->first();
        return response()->json($budget_allocation, 200);
    }

    public function updateBudgetAllocation(BudgetAllocation $budget_allocation, Request $request, SettingService $settingService)
    {
        $this->authorize('tfd/configuração atualizar');
        return $settingService->updateBudgetAllocation($budget_allocation, $request);
    }
}
