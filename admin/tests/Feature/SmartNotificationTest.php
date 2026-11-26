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

    protected function setUp(): void
    {
        parent::setUp();
        $host = (string) config('database.connections.mysql.host');
        $name = (string) config('database.connections.mysql.database');
        if ($host !== '127.0.0.1' || $name !== 'netcell_recharge') {
            $this->fail('Refusing admin notification tests against '.$host.'/'.$name);
        }
        UserAppCampaignDispatcher::$testCalls = [];
    }

    protected function tearDown(): void
    {
        if ($this->campaignIds !== []) {
            DB::table('notification_campaign_events')->whereIn('campaign_id', $this->campaignIds)->delete();
            DB::table('notification_campaigns')->whereIn('id', $this->campaignIds)->delete();
        }
        DB::table('notification_campaign_events')->whereIn('campaign_id', function ($query) {
            $query->select('id')->from('notification_campaigns')->where('title', 'Phase2C test title');
        })->delete();
        DB::table('notification_campaigns')->where('title', 'Phase2C test title')->delete();
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

    public function test_preview_counts_retailers_for_a_wallet_campaign(): void
    {
        $eligible = (int) DB::table('users')
            ->where('status', 1)
            ->where('role_id', 6)
            ->where(function ($inner) {
                $inner->where('deleted_at', 0)->orWhereNull('deleted_at');
            })
            ->count();

        $this->actingAsAdmin($this->adminUser())
            ->post('/admin/extras/smart-notifications/preview', $this->payload('wallet', 'role:6', 'now'))
            ->assertOk()
            ->assertSee('data-eligible="'.$eligible.'"', false)
            ->assertSee('Promotional consent does not block');
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
            ->assertSee('allowed promotional notifications')
            ->assertSee('data-eligible="'.$consented.'"', false);
    }

    public function test_schedule_is_saved_and_not_dispatched(): void
    {
        $when = Carbon::now()->addDay();
        $response = $this->actingAsAdmin($this->adminUser())
            ->post('/admin/extras/smart-notifications/preview', $this->payload('maintenance', 'role:6', 'schedule', [
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
        $this->actingAsAdmin($this->adminUser())
            ->post('/admin/extras/smart-notifications/preview', $this->payload('security', 'role:6', 'now'))
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
        $this->actingAsAdmin($this->adminUser())
            ->post('/admin/extras/smart-notifications/preview', $this->payload('refund', 'role:6', 'now'))
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

    public function test_existing_send_message_page_still_opens(): void
    {
        $this->actingAsAdmin($this->adminUser())
            ->get('/admin/users/send-message')
            ->assertOk();
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
