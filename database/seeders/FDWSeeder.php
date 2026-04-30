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
        $sql = 'CREATE EXTENSION IF NOT EXISTS postgres_fdw;';
        $sql .= 'DROP SERVER IF EXISTS '.config('fdw.auth.module').' CASCADE;';
        $sql .= 'DROP SERVER IF EXISTS '.config('fdw.core.module').' CASCADE;';
        $sql .= 'DROP SERVER IF EXISTS '.config('fdw.datasus.module').' CASCADE;';
        $sql .= 'DROP SERVER IF EXISTS '.config('fdw.storage.module').' CASCADE;';
        DB::unprepared($sql);

        $sql = 'CREATE SERVER '.config('fdw.auth.module').' FOREIGN DATA WRAPPER postgres_fdw OPTIONS (host \''.config('fdw.auth.host').'\', dbname \''.config('fdw.auth.database').'\');';
        $sql .= 'CREATE USER MAPPING FOR '.config('fdw.auth.user').' SERVER '.config('fdw.auth.module').' OPTIONS (user \''.config('fdw.auth.user').'\', password \''.config('fdw.auth.password').'\');';
        $sql .= 'ALTER SERVER '.config('fdw.auth.module').' OPTIONS (ADD updatable \'true\');';
        $sql .= 'IMPORT FOREIGN SCHEMA public LIMIT TO (users,permissions,roles,model_has_permissions,model_has_roles,role_has_permissions,modules,user_modules) FROM SERVER '.config('fdw.auth.module').' INTO public;';
        DB::unprepared($sql);

        $sql = 'CREATE SERVER '.config('fdw.core.module').' FOREIGN DATA WRAPPER postgres_fdw OPTIONS (host \''.config('fdw.core.host').'\', dbname \''.config('fdw.core.database').'\');';
        $sql .= 'CREATE USER MAPPING FOR '.config('fdw.core.user').' SERVER '.config('fdw.core.module').' OPTIONS (user \''.config('fdw.core.user').'\', password \''.config('fdw.core.password').'\');';
        $sql .= 'ALTER SERVER '.config('fdw.core.module').' OPTIONS (ADD updatable \'true\');';
        $sql .= 'IMPORT FOREIGN SCHEMA public LIMIT TO (patients,patient_cares,hospital_unities) FROM SERVER '.config('fdw.core.module').' INTO public;';
        DB::unprepared($sql);

        $sql = 'CREATE SERVER '.config('fdw.datasus.module').' FOREIGN DATA WRAPPER postgres_fdw OPTIONS (host \''.config('fdw.datasus.host').'\', dbname \''.config('fdw.datasus.database').'\');';
        $sql .= 'CREATE USER MAPPING FOR '.config('fdw.datasus.user').' SERVER '.config('fdw.datasus.module').' OPTIONS (user \''.config('fdw.datasus.user').'\', password \''.config('fdw.datasus.password').'\');';
        $sql .= 'ALTER SERVER '.config('fdw.datasus.module').' OPTIONS (ADD updatable \'true\');';
        $sql .= 'IMPORT FOREIGN SCHEMA public LIMIT TO (competences,cids) FROM SERVER '.config('fdw.datasus.module').' INTO public;';
        DB::unprepared($sql);

        $sql = 'CREATE SERVER '.config('fdw.storage.module').' FOREIGN DATA WRAPPER postgres_fdw OPTIONS (host \''.config('fdw.storage.host').'\', dbname \''.config('fdw.storage.database').'\');';
        $sql .= 'CREATE USER MAPPING FOR '.config('fdw.storage.user').' SERVER '.config('fdw.storage.module').' OPTIONS (user \''.config('fdw.storage.user').'\', password \''.config('fdw.storage.password').'\');';
        $sql .= 'ALTER SERVER '.config('fdw.storage.module').' OPTIONS (ADD updatable \'true\');';
        $sql .= 'IMPORT FOREIGN SCHEMA public LIMIT TO (archives) FROM SERVER '.config('fdw.storage.module').' INTO public;';
        DB::unprepared($sql);
    }
}
