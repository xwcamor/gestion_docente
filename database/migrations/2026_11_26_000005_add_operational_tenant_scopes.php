<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    /**
     * Each table below is a tenant-owned root record. Child records inherit
     * their tenant through their existing parent relationships.
     */
    private array $tables = [
        'cycles' => 'professor_id',
        'courses' => 'professor_id',
        'students' => 'professor_id',
        'holidays' => 'professor_id',
        'five_minute_talk_topics' => 'professor_id',
        'audit_logs' => 'professor_id',
    ];

    public function up(): void
    {
        $activeInstitutions = DB::table('institutions')
            ->where('status', 'active')
            ->orderBy('id')
            ->limit(2)
            ->pluck('id');

        $hasOperationalData = collect(array_keys($this->tables))->contains(
            fn (string $table): bool => Schema::hasTable($table) && DB::table($table)->exists()
        );

        if ($hasOperationalData && $activeInstitutions->count() !== 1) {
            throw new RuntimeException(
                'No se puede asignar automáticamente la información operativa: se requiere exactamente una institución activa. Revisa los datos antes de continuar.'
            );
        }

        $institutionId = $activeInstitutions->count() === 1
            ? (int) $activeInstitutions->first()
            : null;

        foreach ($this->tables as $tableName => $afterColumn) {
            if (!Schema::hasColumn($tableName, 'institution_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($afterColumn): void {
                    $table->foreignId('institution_id')
                        ->nullable()
                        ->after($afterColumn)
                        ->constrained('institutions')
                        ->restrictOnDelete();
                });
            }

            if ($institutionId !== null) {
                DB::table($tableName)
                    ->whereNull('institution_id')
                    ->update(['institution_id' => $institutionId]);
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->tables) as $tableName) {
            if (Schema::hasColumn($tableName, 'institution_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropConstrainedForeignId('institution_id');
                });
            }
        }
    }
};
