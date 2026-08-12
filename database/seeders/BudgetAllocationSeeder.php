<?php

namespace Database\Seeders;

use App\Models\BudgetAllocation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BudgetAllocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BudgetAllocation::insert([
            [
                'program' => '0526e',
                'active_project' => '2545',
                'nature_of_expenditure' => '3.3.90.00.00',
                'source' => '2.600.0000',
            ],
        ]);
    }
}
