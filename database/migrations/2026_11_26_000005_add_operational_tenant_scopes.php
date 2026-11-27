<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        // Existing accounts belong to the only active tenant in this installation.
        // This keeps legacy logins working after membership-based tenant resolution.
        if ($institutionId !== null) {
            $existingUsersWithoutMembership = DB::table('users as tenant_users')
                ->selectRaw(
                    '? as institution_id,
                    tenant_users.id as user_id,
                    CASE WHEN tenant_users.role = ? THEN ? ELSE ? END as role,
                    ? as status,
                    NULL as starts_on,
                    NULL as ends_on,
                    NOW() as created_at,
                    NOW() as updated_at',
                    [$institutionId, 'admin', 'admin', 'teacher', 'active']
                )
                ->whereNotExists(function ($query) use ($institutionId): void {
                    $query->selectRaw('1')
                        ->from('institution_memberships')
                        ->whereColumn('institution_memberships.user_id', 'tenant_users.id')
                        ->where('institution_memberships.institution_id', $institutionId);
                });

            DB::table('institution_memberships')->insertUsing(
                [
                    'institution_id',
                    'user_id',
                    'role',
                    'status',
                    'starts_on',
                    'ends_on',
                    'created_at',
                    'updated_at',
                ],
                $existingUsersWithoutMembership
            );
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
