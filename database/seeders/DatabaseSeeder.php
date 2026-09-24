<?php

namespace Database\Seeders;

use App\Enums\Professionals; // Ajuste o namespace do Enum de Profissionais se necessário
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            FDWSeeder::class,
            DailyCostSeeder::class,
            BudgetAllocationSeeder::class,
        ]);

        $admUser = User::where('email', 'admin@tfd.com')->first();

        if ($admUser) {
            // 1. Garante ou cria o registro do profissional para o admin
            $professional = $admUser->professional()->firstOrCreate(
                ['user_id' => $admUser->id],
                [
                    'name'         => $admUser->name ?? 'Administrador',
                    'cns'          => '000000000000000',
                    'registration' => '000000',
                ]
            );

            // 2. Associa a lotação/tipo 'Administrador' via relacionamento do Model
            if (!$professional->types()->where('type', 'Administrador')->exists()) {
                $professional->types()->create([
                    'type' => 'Administrador'
                ]);
            }
        }
    }
}