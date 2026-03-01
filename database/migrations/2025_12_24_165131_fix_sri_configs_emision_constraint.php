<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 0) Quitar constraint anterior (si existe)
        DB::statement("ALTER TABLE sri_configs DROP CONSTRAINT IF EXISTS sri_configs_emision_check");

        // 1) Quitar DEFAULT antes de cambiar tipo (si existe)
        DB::statement("ALTER TABLE sri_configs ALTER COLUMN emision DROP DEFAULT");

        // 2) Cambiar tipo a SMALLINT manejando textos comunes
        DB::statement("
            ALTER TABLE sri_configs
            ALTER COLUMN emision TYPE SMALLINT
            USING (
                CASE
                    WHEN emision IS NULL THEN 1
                    WHEN emision::text ILIKE 'NORMAL' THEN 1
                    WHEN emision::text ILIKE 'EMISION_NORMAL' THEN 1
                    ELSE emision::text::smallint
                END
            )
        ");

        // 3) Default correcto
        DB::statement("ALTER TABLE sri_configs ALTER COLUMN emision SET DEFAULT 1");

        // 4) Check correcto (por ahora solo 1)
        DB::statement("ALTER TABLE sri_configs ADD CONSTRAINT sri_configs_emision_check CHECK (emision IN (1))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE sri_configs DROP CONSTRAINT IF EXISTS sri_configs_emision_check");
        DB::statement("ALTER TABLE sri_configs ALTER COLUMN emision DROP DEFAULT");
    }
};
