<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationReadStatusTest extends TestCase
{
    /** @var array<int, int> */
    private array $messageIds = [];

    /** @var array<int, int> */
    private array $campaignIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $host = (string) config('database.connections.mysql.host');
        $name = (string) config('database.connections.mysql.database');
        if ($host !== '127.0.0.1' || $name !== 'netcell_recharge') {
            $this->fail('Refusing read-status tests against '.$host.'/'.$name);
        }
        if (! DB::getSchemaBuilder()->hasColumn('messages', 'read_at')) {
            $this->fail('messages.read_at is missing. Run the local read_at migration first.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->messageIds !== []) {
            DB::table('messages')->whereIn('id', $this->messageIds)->delete();
        }
        if ($this->campaignIds !== []) {
            DB::table('notification_campaign_events')->whereIn('campaign_id', $this->campaignIds)->delete();
            DB::table('messages')->whereIn('campaign_id', $this->campaignIds)->delete();
            DB::table('notification_campaigns')->whereIn('id', $this->campaignIds)->delete();
        }
        parent::tearDown();
    }

    public function test_mark_read_does_not_change_campaign_delivery_status(): void
    {
        $user = $this->retailer();
        $pendingCampaign = $this->campaign(0, 0);
        $sentCampaign = $this->campaign(1, 0);
        $failedCampaign = $this->campaign(0, 1);
        $pending = $this->message($user->id, $pendingCampaign, 0, 'Phase1 read pending');
        $sent = $this->message($user->id, $sentCampaign, 1, 'Phase1 read sent');
        $failed = $this->message($user->id, $failedCampaign, 3, 'Phase1 read failed');

        \helpers::markNotificationsRead((int) $user->id, [$pending, $sent, $failed]);

        $this->assertSame(0, $this->status($pending));
        $this->assertSame(1, $this->status($sent));
        $this->assertSame(3, $this->status($failed));
        $this->assertNotNull(DB::table('messages')->where('id', $pending)->value('read_at'));
        $this->assertSame(0, (int) DB::table('notification_campaigns')->where('id', $pendingCampaign)->value('success_count'));
        $this->assertSame(1, (int) DB::table('notification_campaigns')->where('id', $sentCampaign)->value('success_count'));
        $this->assertSame(1, (int) DB::table('notification_campaigns')->where('id', $failedCampaign)->value('failure_count'));
    }

    public function test_history_shows_read_state_without_changing_delivery_status(): void
    {
        $user = $this->retailer();
        $campaignId = $this->campaign();
        $pending = $this->message($user->id, $campaignId, 0, 'Phase1 history pending');
        $manual = $this->message($user->id, null, 0, 'Phase1 history manual');

        $before = $this->historyItem((int) $user->id, 'Phase1 history pending');
        $this->assertSame(0, (int) $before->status);
        $this->assertSame(0, (int) $before->is_read);

        $this->postJson('/api/v1/notifications/read', [
            'login_key' => $user->login_key,
            'user_id' => $user->id,
            'notification_ids' => [$pending, $manual],
        ])->assertOk()->assertJsonPath('type', 'success');

        $after = $this->historyItem((int) $user->id, 'Phase1 history pending');
        $manualAfter = $this->historyItem((int) $user->id, 'Phase1 history manual');
        $this->assertSame(0, (int) $after->status);
        $this->assertSame(1, (int) $after->is_read);
        $this->assertNotNull($after->read_at);
        $this->assertSame(1, (int) $manualAfter->status);
        $this->assertSame(1, (int) $manualAfter->is_read);
        $this->assertSame(0, $this->status($pending));
        $stillUnread = DB::table('messages')
            ->where('to_user_id', $user->id)
            ->where('msg_source', 'PUSH')
            ->where(function ($inner) {
                $inner->where(function ($legacy) {
                    $legacy->whereNull('campaign_id')->where('status', 0);
                })->orWhere(function ($campaign) {
                    $campaign->whereNotNull('campaign_id')->whereNull('read_at');
                });
            })
            ->whereNotIn('id', [$pending, $manual])
            ->count();
        $this->assertSame($stillUnread, \helpers::countUnreadNotifications((int) $user->id));

        $listed = collect($this->postJson('/api/v1/notifications', [
            'login_key' => $user->login_key,
            'user_id' => $user->id,
        ])->assertOk()->json('data.notifications'));
        $row = $listed->firstWhere('subject', 'Phase1 history pending');
        $this->assertNotNull($row);
        $this->assertSame(0, (int) $row['status']);
        $this->assertSame(1, (int) $row['is_read']);
    }

    private function historyItem(int $userId, string $subject): object
    {
        $row = \helpers::fetchUserNotifications($userId, 50)->firstWhere('subject', $subject);
        $this->assertNotNull($row);

        return $row;
    }

    private function retailer(): object
    {
        $user = DB::table('users')->where('role_id', 6)->where('status', 1)->orderBy('id')->first();
        if (! $user) {
            $this->fail('No active retailer in the local database.');
        }

        return $user;
    }

    private function campaign(int $success = 0, int $failure = 0): int
    {
        $id = (int) DB::table('notification_campaigns')->insertGetId([
            'campaign_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'title' => 'Phase1 read status',
            'message' => 'Phase1 read body',
            'category' => 'maintenance',
            'priority' => 'normal',
            'target_type' => 'role',
            'target_role_id' => 6,
            'status' => 'processing',
            'total_recipients' => 1,
            'success_count' => $success,
            'failure_count' => $failure,
            'skipped_count' => 0,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->campaignIds[] = $id;

        return $id;
    }

    private function message(int $userId, ?int $campaignId, int $status, string $subject): int
    {
        $id = (int) DB::table('messages')->insertGetId([
            'user_id' => 1,
            'to_user_id' => $userId,
            'subject' => $subject,
            'msg_source' => 'PUSH',
            'template_id' => '0',
            'content' => 'Phase1 read body',
            'status' => $status,
            'campaign_id' => $campaignId,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->messageIds[] = $id;

        return $id;
    }

    private function status(int $id): int
    {
        return (int) DB::table('messages')->where('id', $id)->value('status');
    }
}
