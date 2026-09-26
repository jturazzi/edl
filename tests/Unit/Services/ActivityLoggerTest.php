<?php

namespace Tests\Unit\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_creates_activity_log_entry(): void
    {
        ActivityLogger::log('custom_action', 'edl', 42, ['foo' => 'bar']);

        $this->assertDatabaseHas('activity_logs', [
            'action'      => 'custom_action',
            'entity_type' => 'edl',
            'entity_id'   => 42,
        ]);

        $log = ActivityLog::first();
        $this->assertSame(['foo' => 'bar'], $log->details);
    }

    public function test_log_stores_null_details_when_empty_array_given(): void
    {
        ActivityLogger::log('custom_action', 'edl', 1, []);

        $log = ActivityLog::first();
        $this->assertNull($log->details);
    }

    public function test_log_captures_authenticated_user_id(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        ActivityLogger::log('custom_action', 'edl', 1);

        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id]);
    }

    public function test_edl_completed_logs_expected_action(): void
    {
        ActivityLogger::edlCompleted(7, ['adresse' => 'X']);

        $this->assertDatabaseHas('activity_logs', [
            'action'      => 'edl_completed',
            'entity_type' => 'edl',
            'entity_id'   => 7,
        ]);
    }

    public function test_edl_deleted_logs_expected_action(): void
    {
        ActivityLogger::edlDeleted(7, ['adresse' => 'X']);

        $this->assertDatabaseHas('activity_logs', [
            'action'      => 'edl_deleted',
            'entity_type' => 'edl',
            'entity_id'   => 7,
        ]);
    }

}
