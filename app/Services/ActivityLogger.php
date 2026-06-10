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

    public static function categoryCreated(int $categoryId, array $details): void
    {
        static::log('category_created', 'category', $categoryId, $details);
    }

    public static function categoryDeleted(int $categoryId, array $details): void
    {
        static::log('category_deleted', 'category', $categoryId, $details);
    }
}
