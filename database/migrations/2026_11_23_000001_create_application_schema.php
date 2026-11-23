<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\DB;

return new class extends Migration
{
    private function schemaStatements(): array
    {
        $path = database_path('schema/gestion_docente.sql');

        if (! is_file($path)) {
            throw new RuntimeException("No se encontró el esquema base: {$path}");
        }

        $sql = file_get_contents($path);

        preg_match_all(
            '/CREATE TABLE\\s+`?([A-Za-z0-9_]+)`?\\s*\\([\\s\\S]*?\\)\\s*ENGINE\\s*=\\s*InnoDB\\s*;/i',
            $sql,
            $matches,
            PREG_SET_ORDER
        );

        if (count($matches) !== 28) {
            throw new RuntimeException(
                'El esquema base está incompleto. Se esperaban 28 tablas y se encontraron '.count($matches).'.'
            );
        }

        return $matches;
    }

    public function up(): void
    {
        $statements = $this->schemaStatements();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($statements as $statement) {
                DB::unprepared($statement[0]);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        $statements = array_reverse($this->schemaStatements());

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($statements as $statement) {
                preg_match('/CREATE TABLE\\s+`?([A-Za-z0-9_]+)`?/i', $statement[0], $table);
                if (! empty($table[1])) {
                    DB::statement('DROP TABLE IF EXISTS `'.$table[1].'`');
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};
