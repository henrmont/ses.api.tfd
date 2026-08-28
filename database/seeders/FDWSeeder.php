<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FDWSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = ['auth', 'core', 'datasus', 'storage'];

        // 1. Prepara a extensão e limpa servidores antigos
        $sql = 'CREATE EXTENSION IF NOT EXISTS postgres_fdw;';
        foreach ($modules as $module) {
            $sql .= 'DROP SERVER IF EXISTS ' . config("fdw.{$module}.module") . ' CASCADE;';
        }
        DB::unprepared($sql);

        // 2. Mapeamento das tabelas por módulo
        $tablesMap = [
            'auth' => 'users,permissions,roles,model_has_permissions,model_has_roles,role_has_permissions,modules,user_modules',
            'core' => 'patients,patient_cares,hospital_unities',
            'datasus' => 'competences,cids',
            'storage' => 'archives',
        ];

        // 3. Criação dinâmica dos SERVERS, USER MAPPINGS e IMPORT SCHEMA
        foreach ($tablesMap as $key => $tables) {
            $module   = config("fdw.{$key}.module");
            $host     = config("fdw.{$key}.host");
            $port     = config("fdw.{$key}.port", '5432');
            $database = config("fdw.{$key}.database");
            $user     = config("fdw.{$key}.user");
            $password = config("fdw.{$key}.password");

            $sql  = "CREATE SERVER {$module} FOREIGN DATA WRAPPER postgres_fdw OPTIONS (host '{$host}', port '{$port}', dbname '{$database}');";
            $sql .= "CREATE USER MAPPING FOR CURRENT_USER SERVER {$module} OPTIONS (user '{$user}', password '{$password}');";
            $sql .= "ALTER SERVER {$module} OPTIONS (ADD updatable 'true');";
            $sql .= "IMPORT FOREIGN SCHEMA public LIMIT TO ({$tables}) FROM SERVER {$module} INTO public;";

            DB::unprepared($sql);
        }
    }
}