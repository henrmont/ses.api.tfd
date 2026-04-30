<?php

namespace Database\Seeders;

use App\Models\DailyCost;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DailyCostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DailyCost::insert([
            [
                'name' => 'Diária de paciente com pernoite',
                'value' => 200.00,
                'sigtap_code' => '0803010036',
                'overnight' => true,
            ],
            [
                'name' => 'Diária de acompanhante com pernoite',
                'value' => 200.00,
                'sigtap_code' => '0803010060',
                'overnight' => true,
            ],
            [
                'name' => 'Diária de paciente sem pernoite',
                'value' => 100.00,
                'sigtap_code' => '0803010036',
                'overnight' => false,
            ],
            [
                'name' => 'Diária de acompanhante sem pernoite',
                'value' => 100.00,
                'sigtap_code' => '0803010060',
                'overnight' => false,
            ]
        ]);
    }
}
