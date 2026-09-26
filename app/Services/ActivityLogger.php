<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    public static function log(string $action, string $entityType, ?int $entityId, array $details = []): void
    {
        ActivityLog::create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'details'     => $details ?: null,
        ]);
    }

    public static function edlCompleted(int $edlId, array $details): void
    {
        static::log('edl_completed', 'edl', $edlId, $details);
    }

    public static function edlDeleted(int $edlId, array $details): void
    {
        static::log('edl_deleted', 'edl', $edlId, $details);
    }

    public static function edlArchived(int $edlId, array $details): void
    {
        static::log('edl_archived', 'edl', $edlId, $details);
    }

    public static function edlUnarchived(int $edlId, array $details): void
    {
        static::log('edl_unarchived', 'edl', $edlId, $details);
    }

    public static function edlDuplicated(int $edlId, array $details): void
    {
        static::log('edl_duplicated', 'edl', $edlId, $details);
    }

    /** Connexions : IP et navigateur conservés pour retrouver une utilisation suspecte. */
    public static function userLogin(int $userId, array $details = []): void
    {
        static::log('user_login', 'user', $userId, $details);
    }

    public static function userLogout(int $userId, array $details = []): void
    {
        static::log('user_logout', 'user', $userId, $details);
    }

    public static function loginFailed(array $details = []): void
    {
        static::log('login_failed', 'user', null, $details);
    }

    public static function userRoleChanged(int $userId, array $details): void
    {
        static::log('user_role_changed', 'user', $userId, $details);
    }
}
