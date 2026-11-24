<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function record(
        string $module,
        string $event,
        string $description,
        ?Model $subject = null,
        ?array $before = null,
        ?array $after = null
    ): void {
        $user = Auth::user();

        AuditLog::create([
            'user_id' => $user?->id,
            'professor_id' => $user?->professor?->id,
            'module' => $module,
            'event' => $event,
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'description' => mb_substr($description, 0, 500),
            'before_data' => $this->safeData($before),
            'after_data' => $this->safeData($after),
            'ip_address' => request()?->ip(),
            'user_agent' => mb_substr((string) request()?->userAgent(), 0, 1000),
        ]);
    }

    public function safeData(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $blocked = ['password', 'remember_token', 'token', 'secret', 'api_key', 'access_token'];
        $safe = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), $blocked, true)) {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $safe[$key] = is_string($value) ? mb_substr($value, 0, 500) : $value;
            } elseif (is_array($value)) {
                $safe[$key] = array_slice($value, 0, 30, true);
            }
        }

        return $safe;
    }
}
