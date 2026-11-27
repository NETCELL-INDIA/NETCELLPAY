<?php

namespace Tests\Feature;

use App\Services\UserAppCampaignDispatcher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SmartNotificationTest extends TestCase
{
    /** @var array<int, int> */
    private array $campaignIds = [];

    /** @var array<int, int> */
    private array $consentRestore = [];

    protected function setUp(): void
    {
        parent::setUp();
        $host = (string) config('database.connections.mysql.host');
        $name = (string) config('database.connections.mysql.database');
        if ($host !== '127.0.0.1' || $name !== 'netcell_recharge') {
            $this->fail('Refusing admin notification tests against '.$host.'/'.$name);
        }
        UserAppCampaignDispatcher::$testCalls = [];
        UserAppCampaignDispatcher::$testRetryIds = [];
    }

    protected function tearDown(): void
    {
        if ($this->campaignIds !== []) {
            DB::table('messages')->whereIn('campaign_id', $this->campaignIds)->delete();
            DB::table('notification_campaign_events')->whereIn('campaign_id', $this->campaignIds)->delete();
            DB::table('notification_campaigns')->whereIn('id', $this->campaignIds)->delete();
        }
        DB::table('notification_campaign_events')->whereIn('campaign_id', function ($query) {
            $query->select('id')->from('notification_campaigns')->where('title', 'Phase2C test title');
        })->delete();
        DB::table('notification_campaigns')->where('title', 'Phase2C test title')->delete();
        DB::table('messages')->where('subject', 'Phase1 report delete')->delete();
        foreach ($this->consentRestore as $userId => $value) {
            DB::table('users')->where('id', $userId)->update(['promotional_push_consent' => $value]);
        }
        parent::tearDown();
    }

    public function test_guest_cannot_open_the_dashboard(): void
    {
        $this->get('/admin/extras/smart-notifications')->assertRedirect();
    }

    public function test_non_admin_cannot_open_the_dashboard(): void
    {
        $user = DB::table('users')->where('role_id', 6)->where('status', 1)->first();
        if (! $user) {
            $this->markTestSkipped('No active retailer in the local database.');
        }

        $this->actingAsAdmin($user)
            ->get('/admin/extras/smart-notifications')
            ->assertRedirect();
    }

    public function test_dashboard_shows_database_counts(): void
    {
        $admin = $this->adminUser();
        $id = $this->insertCampaign('draft');
        $drafts = (int) DB::table('notification_campaigns')->where('status', 'draft')->count();

        $this->actingAsAdmin($admin)
            ->get('/admin/extras/smart-notifications')
            ->assertOk()
            ->assertSee('Total Campaigns')
            ->assertSee('data-stat="draft_campaigns">'.number_format($drafts), false)
            ->assertSee('Successful Deliveries')
            ->assertSee('Existing SMS / Notification');

        $this->assertSame('draft', DB::table('notification_campaigns')->where('id', $id)->value('status'));
    }

    public function test_preview_rejects_a_transactional_bulk_category(): void
    {
        $this->actingAsAdmin($this->adminUser())
            ->post('/admin/extras/smart-notifications/preview', $this->payload('wallet', 'role:6', 'now'))
            ->assertSessionHasErrors('category');
    }

    public function test_promotional_preview_excludes_users_without_consent(): void
    {
        $consented = (int) DB::table('users')
            ->where('status', 1)
            ->where('role_id', 6)
            ->where('promotional_push_consent', 1)
            ->where(function ($inner) {
                $inner->where('deleted_at', 0)->orWhereNull('deleted_at');
            })
            ->count();

        $this->actingAsAdmin($this->adminUser())
            ->post('/admin/extras/smart-notifications/preview', $this->payload('offers', 'role:6', 'now'))
            ->assertOk()
            ->assertSee('Only users who have explicitly enabled promotional notifications')
            ->assertDontSee('does not block')
            ->assertSee('data-consent-approved="'.$consented.'"', false);
    }

    public function test_schedule_is_saved_and_not_dispatched(): void
    {
        $this->grantRetailerConsent();
        $when = Carbon::now()->addDay();
        $response = $this->actingAsAdmin($this->adminUser())
            ->post('/admin/extras/smart-notifications/preview', $this->payload('offers', 'role:6', 'schedule', [
                'schedule_date' => $when->format('Y-m-d'),
                'schedule_time' => $when->format('H:i'),
            ]));
        $response->assertOk();
        $uuid = session('smart_notification_draft.uuid');

        $this->post('/admin/extras/smart-notifications/confirm', ['campaign_uuid' => $uuid])
            ->assertRedirect('/admin/extras/smart-notifications');

        $row = DB::table('notification_campaigns')->where('campaign_uuid', $uuid)->first();
        $this->campaignIds[] = (int) $row->id;
        $this->assertSame('scheduled', $row->status);
        $this->assertSame([], UserAppCampaignDispatcher::$testCalls);
        $this->assertTrue(DB::table('notification_campaign_events')->where('campaign_id', $row->id)->where('action', 'created')->exists());
        $note = (string) DB::table('notification_campaign_events')->where('campaign_id', $row->id)->value('note');
        $this->assertStringNotContainsString('Phase2C test title', $note);
    }

    public function test_send_now_dispatches_the_user_app_command_without_sending_here(): void
    {
        $this->grantRetailerConsent();
        $this->actingAsAdmin($this->adminUser())
            ->post('/admin/extras/smart-notifications/preview', $this->payload('offers', 'role:6', 'now'))
            ->assertOk();
        $uuid = session('smart_notification_draft.uuid');

        $this->post('/admin/extras/smart-notifications/confirm', ['campaign_uuid' => $uuid])
            ->assertRedirect('/admin/extras/smart-notifications');

        $row = DB::table('notification_campaigns')->where('campaign_uuid', $uuid)->first();
        $this->campaignIds[] = (int) $row->id;
        $this->assertSame([(int) $row->id], UserAppCampaignDispatcher::$testCalls);
        $this->assertSame('processing', $row->status);
        $this->assertSame(0, (int) DB::table('messages')->where('campaign_id', $row->id)->count());
    }

    public function test_second_confirm_does_not_create_another_campaign(): void
    {
        $this->grantRetailerConsent();
        $this->actingAsAdmin($this->adminUser())
            ->post('/admin/extras/smart-notifications/preview', $this->payload('offers', 'role:6', 'now'))
            ->assertOk();
        $draft = session('smart_notification_draft');
        $this->post('/admin/extras/smart-notifications/confirm', ['campaign_uuid' => $draft['uuid']]);
        session()->put('smart_notification_draft', $draft);
        $this->post('/admin/extras/smart-notifications/confirm', ['campaign_uuid' => $draft['uuid']])
            ->assertRedirect('/admin/extras/smart-notifications/create');

        $count = (int) DB::table('notification_campaigns')->where('campaign_uuid', $draft['uuid'])->count();
        $this->assertSame(1, $count);
        $this->campaignIds[] = (int) DB::table('notification_campaigns')->where('campaign_uuid', $draft['uuid'])->value('id');
    }

    public function test_scheduled_campaign_can_be_cancelled_before_send(): void
    {
        $id = $this->insertCampaign('scheduled');

        $this->actingAsAdmin($this->adminUser())
            ->post('/admin/extras/smart-notifications/'.$id.'/cancel')
            ->assertRedirect('/admin/extras/smart-notifications/'.$id);

        $this->assertSame('cancelled', DB::table('notification_campaigns')->where('id', $id)->value('status'));
        $this->assertSame([], UserAppCampaignDispatcher::$testCalls);
        $this->assertTrue(DB::table('notification_campaign_events')->where('campaign_id', $id)->where('action', 'cancelled')->exists());
    }

    public function test_retry_requests_only_the_failed_campaign(): void
    {
        $id = $this->insertCampaign('failed');
        DB::table('messages')->insert([
            'user_id' => 1,
            'to_user_id' => 2,
            'subject' => 'Phase2C test title',
            'msg_source' => 'PUSH',
            'template_id' => '0',
            'content' => 'Hello',
            'status' => 3,
            'campaign_id' => $id,
            'delivery_error' => 'temporary failure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsAdmin($this->adminUser())
            ->post('/admin/extras/smart-notifications/'.$id.'/retry')
            ->assertRedirect('/admin/extras/smart-notifications/'.$id);

        $this->assertSame([$id], UserAppCampaignDispatcher::$testRetryIds);
        $this->actingAsAdmin($this->adminUser())
            ->get('/admin/extras/smart-notifications/'.$id)
            ->assertOk()
            ->assertSee('Retry failed recipients')
            ->assertDontSee('temporary failure token');
    }

    public function test_completed_campaign_cannot_be_cancelled(): void
    {
        $id = $this->insertCampaign('completed');

        $this->actingAsAdmin($this->adminUser())
            ->post('/admin/extras/smart-notifications/'.$id.'/cancel')
            ->assertRedirect('/admin/extras/smart-notifications');

        $this->assertSame('completed', DB::table('notification_campaigns')->where('id', $id)->value('status'));
    }

    public function test_consent_history_page_cannot_change_consent(): void
    {
        $logId = DB::table('notification_consent_logs')->insertGetId([
            'user_id' => 1,
            'channel' => 'push',
            'consent_type' => 'promotional_push',
            'previous_value' => 0,
            'new_value' => 1,
            'action' => 'granted',
            'source' => 'app',
            'created_at' => now(),
        ]);

        $this->get('/admin/extras/notification-consent')->assertRedirect();
        $this->actingAsAdmin($this->adminUser())
            ->get('/admin/extras/notification-consent?user_id=1')
            ->assertOk()
            ->assertSee('Consent changes')
            ->assertSee('granted')
            ->assertDontSee('name="promotional_push_consent"', false);

        DB::table('notification_consent_logs')->where('id', $logId)->delete();
        $this->assertSame(0, (int) DB::table('users')->where('id', 1)->value('promotional_push_consent'));
    }

    public function test_existing_send_message_page_still_opens(): void
    {
        $this->actingAsAdmin($this->adminUser())
            ->get('/admin/users/send-message')
            ->assertOk();
    }

    public function test_send_report_cannot_delete_a_campaign_with_the_same_text(): void
    {
        $admin = $this->adminUser();
        $campaignId = $this->insertCampaign('completed');
        DB::table('notification_campaigns')->where('id', $campaignId)->update([
            'total_recipients' => 3,
            'success_count' => 2,
            'failure_count' => 1,
        ]);
        $now = now();
        $shared = [
            'user_id' => 1,
            'to_user_id' => 2,
            'subject' => 'Phase1 report delete',
            'msg_source' => 'PUSH',
            'template_id' => '0',
            'content' => 'same body',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $campaignMessage = DB::table('messages')->insertGetId($shared + [
            'status' => 1,
            'campaign_id' => $campaignId,
        ]);
        $manualMessage = DB::table('messages')->insertGetId($shared + [
            'status' => 1,
            'campaign_id' => null,
        ]);

        $this->post('/admin/extras/notification-send-report/delete', ['id' => $manualMessage])
            ->assertRedirect();

        $this->actingAsAdmin($admin)
            ->postJson('/admin/extras/notification-send-report/delete', ['id' => $campaignMessage])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Campaign delivery history cannot be deleted.');

        $this->actingAsAdmin($admin)
            ->postJson('/admin/extras/notification-send-report/delete', ['id' => $manualMessage])
            ->assertOk()
            ->assertJsonPath('type', 'success');

        $this->assertNull(DB::table('messages')->where('id', $manualMessage)->first());
        $this->assertSame(1, (int) DB::table('messages')->where('id', $campaignMessage)->value('status'));
        $campaign = DB::table('notification_campaigns')->where('id', $campaignId)->first();
        $this->assertSame(2, (int) $campaign->success_count);
        $this->assertSame(1, (int) $campaign->failure_count);
        $this->assertSame(3, (int) $campaign->total_recipients);

        $this->actingAsAdmin($admin)
            ->postJson('/admin/extras/notification-send-report/list', ['page' => 1, 'show' => 50])
            ->assertOk()
            ->assertSee('Campaign history', false)
            ->assertDontSee('data-id="'.$campaignMessage.'"', false);
    }

    private function grantRetailerConsent(): void
    {
        $user = DB::table('users')->where('role_id', 6)->where('status', 1)->orderBy('id')->first();
        if (! $user) {
            $this->fail('No active retailer in the local database.');
        }
        $this->consentRestore[(int) $user->id] = (int) $user->promotional_push_consent;
        DB::table('users')->where('id', $user->id)->update(['promotional_push_consent' => 1]);
    }

    private function actingAsAdmin(object $user)
    {
        return $this->withSession([
            'user_id' => (int) $user->id,
            'role_id' => (int) $user->role_id,
            'login_key' => $user->login_key,
        ]);
    }

    private function adminUser(): object
    {
        $user = DB::table('users')->where('role_id', 1)->where('status', 1)->orderBy('id')->first();
        if (! $user) {
            $this->fail('No active superadmin in the local database.');
        }

        return $user;
    }

    private function insertCampaign(string $status): int
    {
        $id = (int) DB::table('notification_campaigns')->insertGetId([
            'campaign_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'title' => 'Phase2C test title',
            'message' => 'Phase2C test body',
            'category' => 'maintenance',
            'priority' => 'normal',
            'target_type' => 'role',
            'target_role_id' => 6,
            'status' => $status,
            'total_recipients' => 0,
            'success_count' => 0,
            'failure_count' => 0,
            'skipped_count' => 0,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->campaignIds[] = $id;

        return $id;
    }

    private function payload(string $category, string $audience, string $mode, array $extra = []): array
    {
        return array_merge([
            'title' => 'Phase2C test title',
            'message' => 'Hello from the admin composer',
            'category' => $category,
            'priority' => 'normal',
            'audience' => $audience,
            'send_mode' => $mode,
        ], $extra);
    }
}
