<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 0) Quitar constraint anterior (si existe)
        DB::statement("ALTER TABLE sri_configs DROP CONSTRAINT IF EXISTS sri_configs_ambiente_check");

        // 1) Quitar DEFAULT antes de cambiar tipo (causa de tu error)
        DB::statement("ALTER TABLE sri_configs ALTER COLUMN ambiente DROP DEFAULT");

        // 2) Cambiar tipo manejando textos comunes (por si estaba enum/text)
        DB::statement("
            ALTER TABLE sri_configs
            ALTER COLUMN ambiente TYPE SMALLINT
            USING (
                CASE
                    WHEN ambiente IS NULL THEN 1
                    WHEN ambiente::text ILIKE 'PRUEBAS' THEN 1
                    WHEN ambiente::text ILIKE 'TEST' THEN 1
                    WHEN ambiente::text ILIKE 'CERTIFICACION' THEN 1
                    WHEN ambiente::text ILIKE 'PRODUCCION' THEN 2
                    WHEN ambiente::text ILIKE 'PROD' THEN 2
                    ELSE ambiente::text::smallint
                END
            )
        ");

        // 3) Poner DEFAULT numérico
        DB::statement("ALTER TABLE sri_configs ALTER COLUMN ambiente SET DEFAULT 1");

        // 4) Poner CHECK correcto
        DB::statement("ALTER TABLE sri_configs ADD CONSTRAINT sri_configs_ambiente_check CHECK (ambiente IN (1,2))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE sri_configs DROP CONSTRAINT IF EXISTS sri_configs_ambiente_check");
        DB::statement("ALTER TABLE sri_configs ALTER COLUMN ambiente DROP DEFAULT");
        // no revertimos tipo a texto (innecesario)
    }
};
