<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Catat perubahan penting ke dalam audit_logs (SEC-005, BR-015).
     */
    public function log(
        string $action,
        Model|string $entity,
        string|int $entityId,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?string $reason = null,
        ?User $actor = null
    ): AuditLog {
        $user = $actor ?? Auth::user();
        $entityType = is_object($entity) ? class_basename($entity) : $entity;

        return AuditLog::create([
            'actor_id' => $user?->id,
            'actor_role' => $user?->role ?? 'system',
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'reason' => $reason,
            'ip_address' => request()?->ip() ?? '127.0.0.1',
            'user_agent' => substr((string) request()?->userAgent(), 0, 250),
        ]);
    }
}

