<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationCampaign;
use App\Services\FcmHttpV1Service;
use App\Services\NotificationCampaignSender;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationCampaignSenderTest extends TestCase
{
    use DatabaseTransactions;

    private int $sends = 0;

    /** @var array<int, string> */
    private array $bodies = [];

    /** @var array<int, int> */
    private array $userIds = [];

    /** @var array<int, int> */
    private array $campaignIds = [];

    /** @var array<int, int> */
    private array $messageIds = [];

    /** @var array<int, int> */
    private array $jobIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $host = (string) config('database.connections.mysql.host');
        $name = (string) config('database.connections.mysql.database');
        if ($host !== '127.0.0.1' || $name !== 'netcell_recharge') {
            $this->fail('Refusing to run campaign tests against '.$host.'/'.$name);
        }
        NotificationCampaignSender::$onlyUserIds = [];
        NotificationCampaignSender::$beforeSend = null;
        FcmHttpV1Service::fakeHttp(null);
        $this->sends = 0;
        $this->bodies = [];
    }

    protected function tearDown(): void
    {
        FcmHttpV1Service::fakeHttp(null);
        NotificationCampaignSender::$onlyUserIds = null;
        NotificationCampaignSender::$beforeSend = null;
        if ($this->campaignIds !== []) {
            DB::table('notification_campaign_events')->whereIn('campaign_id', $this->campaignIds)->delete();
            DB::table('messages')->whereIn('campaign_id', $this->campaignIds)->delete();
            DB::table('notification_campaigns')->whereIn('id', $this->campaignIds)->delete();
        }
        if ($this->messageIds !== []) {
            DB::table('messages')->whereIn('id', $this->messageIds)->delete();
        }
        if ($this->jobIds !== []) {
            DB::table('jobs')->whereIn('id', $this->jobIds)->where('payload', 'like', '%SendNotificationCampaign%')->delete();
        }
        if ($this->userIds !== []) {
            DB::table('users')->whereIn('id', $this->userIds)->where('password', 'campaign-test')->delete();
        }
        parent::tearDown();
    }

    public function test_valid_token_is_sent_once(): void
    {
        $this->fakeFcm(200, ['name' => 'projects/netcellpay-fe31a/messages/1']);
        $userId = $this->user(6, 1, 'valid-token-aaaaaaaa');
        $campaignId = $this->campaign('user', $userId, null, 'wallet');

        $this->sender()->deliver($campaignId);

        $this->assertSame(1, $this->sends);
        $this->assertSame(1, $this->deliveryStatus($campaignId, $userId));
        $this->assertSame('completed', $this->campaignStatus($campaignId));
        $this->assertSame(1, $this->messageRows($campaignId, $userId));
    }

    public function test_missing_token_is_status_two(): void
    {
        $this->fakeFcm(200, ['name' => 'projects/netcellpay-fe31a/messages/1']);
        $userId = $this->user(6, 1, null);
        $campaignId = $this->campaign('user', $userId, null, 'wallet');

        $this->sender()->deliver($campaignId);

        $this->assertSame(0, $this->sends);
        $this->assertSame(2, $this->deliveryStatus($campaignId, $userId));
        $this->assertSame('no_token', $this->deliveryError($campaignId, $userId));
        $this->assertSame('failed', $this->campaignStatus($campaignId));
    }

    public function test_invalid_token_is_cleared_and_not_logged(): void
    {
        $token = 'invalid-token-bbbbbbbb';
        $this->fakeFcm(404, ['error' => ['status' => 'UNREGISTERED', 'message' => 'gone']]);
        $userId = $this->user(6, 1, $token);
        $campaignId = $this->campaign('user', $userId, null, 'security');

        $this->sender()->deliver($campaignId);

        $row = DB::table('users')->where('id', $userId)->first(['android_fcm_token', 'fcm_token', 'device_token']);
        $this->assertNull($row->android_fcm_token);
        $this->assertNull($row->fcm_token);
        $this->assertNull($row->device_token);
        $this->assertSame(3, $this->deliveryStatus($campaignId, $userId));
        $this->assertStringNotContainsString($token, (string) $this->deliveryError($campaignId, $userId));
        $this->assertStringNotContainsString('PRIVATE KEY', (string) $this->deliveryError($campaignId, $userId));
    }

    public function test_fcm_server_failure_stays_pending_for_retry(): void
    {
        $this->fakeFcm(500, ['error' => ['status' => 'INTERNAL']]);
        $userId = $this->user(6, 1, 'server-token-cccccccc');
        $campaignId = $this->campaign('user', $userId, null, 'wallet');

        try {
            $this->sender()->deliver($campaignId, false, 1, 3);
            $this->fail('Transient FCM failure should raise a retry.');
        } catch (\RuntimeException $e) {
            $this->assertStringNotContainsString('server-token-cccccccc', $e->getMessage());
        }

        $this->assertSame(0, $this->deliveryStatus($campaignId, $userId));
        $this->assertSame('processing', $this->campaignStatus($campaignId));
    }

    public function test_network_timeout_stays_pending_for_retry(): void
    {
        $this->fakeFcm(0, []);
        $userId = $this->user(6, 1, 'timeout-token-dddddddd');
        $campaignId = $this->campaign('user', $userId, null, 'recharge');

        try {
            $this->sender()->deliver($campaignId, false, 1, 3);
            $this->fail('A network timeout should raise a retry.');
        } catch (\RuntimeException $e) {
            $this->assertStringNotContainsString('timeout-token-dddddddd', $e->getMessage());
        }

        $this->assertSame(0, $this->deliveryStatus($campaignId, $userId));
        $this->assertSame('processing', $this->campaignStatus($campaignId));
        $this->assertStringNotContainsString('timeout-token-dddddddd', (string) $this->deliveryError($campaignId, $userId));
    }

    public function test_duplicate_campaign_recipient_keeps_one_row(): void
    {
        $this->fakeFcm(200, ['name' => 'projects/netcellpay-fe31a/messages/1']);
        $userId = $this->user(6, 1, 'dup-token-eeeeeeee');
        $campaignId = $this->campaign('user', $userId, null, 'wallet');
        $sender = $this->sender();
        $sender->deliver($campaignId);
        $sender->deliver($campaignId);

        $this->assertSame(1, $this->sends);
        $this->assertSame(1, $this->messageRows($campaignId, $userId));
    }

    public function test_retry_failed_recipient_only(): void
    {
        $token = 'retry-token-ffffffff';
        $failedAttempts = 0;
        FcmHttpV1Service::fakeHttp(function (string $url, string $body) use ($token, &$failedAttempts) {
            $this->sends++;
            $decoded = json_decode($body, true);
            $sentToken = (string) ($decoded['message']['token'] ?? '');
            if ($sentToken === $token) {
                $failedAttempts++;
                if ($failedAttempts === 1) {
                    return ['status' => 404, 'body' => json_encode(['error' => ['status' => 'UNREGISTERED', 'message' => 'gone']])];
                }
            }

            return ['status' => 200, 'body' => json_encode(['name' => 'projects/netcellpay-fe31a/messages/1'])];
        });
        $failedId = $this->user(6, 1, $token);
        $sentId = $this->user(6, 1, 'already-sent-gggggggg');
        $campaignId = $this->campaign('role', null, 6, 'maintenance');
        $this->sender()->deliver($campaignId);
        $this->assertSame(3, $this->deliveryStatus($campaignId, $failedId));
        $this->assertSame(1, $this->deliveryStatus($campaignId, $sentId));

        DB::table('users')->where('id', $failedId)->update([
            'android_fcm_token' => $token,
            'fcm_token' => $token,
            'device_token' => $token,
        ]);
        $this->fakeFcm(200, ['name' => 'projects/netcellpay-fe31a/messages/2']);
        $before = $this->sends;
        $this->sender()->deliver($campaignId, true);

        $this->assertSame($before + 1, $this->sends);
        $this->assertSame(1, $this->deliveryStatus($campaignId, $failedId));
        $this->assertSame(1, $this->deliveryStatus($campaignId, $sentId));
        $this->assertSame(1, $this->messageRows($campaignId, $failedId));
        $this->assertTrue(DB::table('notification_campaign_events')->where('campaign_id', $campaignId)->where('action', 'retry')->exists());
    }

    public function test_successful_recipient_is_not_resent(): void
    {
        $this->fakeFcm(200, ['name' => 'projects/netcellpay-fe31a/messages/1']);
        $userId = $this->user(4, 1, 'once-token-hhhhhhhh');
        $campaignId = $this->campaign('role', null, 4, 'wallet');
        $sender = $this->sender();
        $sender->deliver($campaignId);
        DB::table('notification_campaigns')->where('id', $campaignId)->update(['status' => 'processing']);
        $sender->deliver($campaignId, true);

        $this->assertSame(1, $this->sends);
    }

    public function test_cancelled_campaign_stops(): void
    {
        $this->fakeFcm(200, ['name' => 'projects/netcellpay-fe31a/messages/1']);
        $userId = $this->user(6, 1, 'cancel-token-iiiiiiii');
        $campaignId = $this->campaign('user', $userId, null, 'wallet');
        DB::table('notification_campaigns')->where('id', $campaignId)->update(['status' => 'cancelled']);

        $this->sender()->deliver($campaignId);

        $this->assertSame(0, $this->sends);
        $this->assertSame(0, $this->messageRows($campaignId, $userId));
        $this->assertSame('cancelled', $this->campaignStatus($campaignId));
    }

    public function test_expired_campaign_stops(): void
    {
        $this->fakeFcm(200, ['name' => 'projects/netcellpay-fe31a/messages/1']);
        $userId = $this->user(6, 1, 'expired-token-jjjjjjjj');
        $campaignId = $this->campaign('user', $userId, null, 'wallet', now()->subMinute());

        $this->sender()->deliver($campaignId);

        $this->assertSame(0, $this->sends);
        $this->assertSame('failed', $this->campaignStatus($campaignId));
        $this->assertSame(0, $this->messageRows($campaignId, $userId));
    }

    public function test_promotional_consent_filters_general_offers_and_announcements(): void
    {
        $this->fakeFcm(200, ['name' => 'projects/netcellpay-fe31a/messages/1']);
        $allowed = $this->user(6, 1, 'promo-yes-kkkkkkkk');
        $blocked = $this->user(6, 0, 'promo-no-llllllll');

        foreach (['general', 'offers', 'announcements'] as $category) {
            $campaignId = $this->campaign('role', null, 6, $category);
            $this->sender()->deliver($campaignId);
            $this->assertSame(1, $this->deliveryStatus($campaignId, $allowed), $category);
            $this->assertSame(0, $this->messageRows($campaignId, $blocked), $category);
        }
    }

    public function test_bulk_categories_cannot_bypass_consent(): void
    {
        $this->fakeFcm(200, ['name' => 'projects/netcellpay-fe31a/messages/1']);
        $userId = $this->user(6, 0, 'txn-token-mmmmmmmm');

        foreach (['wallet', 'recharge', 'refund', 'security', 'maintenance', 'unknown-offer'] as $category) {
            $campaignId = $this->campaign('user', $userId, null, $category);
            $this->sender()->deliver($campaignId);
            $this->assertSame(0, $this->messageRows($campaignId, $userId), $category);
        }
        $this->assertSame(0, $this->sends);
    }

    public function test_revoked_consent_skips_a_pending_promotional_row(): void
    {
        $this->fakeFcm(200, ['name' => 'projects/netcellpay-fe31a/messages/1']);
        $userId = $this->user(6, 0, 'revoked-token-hhhhhhhh');
        $campaignId = $this->campaign('user', $userId, null, 'offers');
        DB::table('messages')->insert([
            'user_id' => 1,
            'to_user_id' => $userId,
            'subject' => 'Pending offer',
            'msg_source' => 'PUSH',
            'template_id' => '0',
            'content' => 'Hello',
            'status' => 0,
            'campaign_id' => $campaignId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->sender()->deliver($campaignId);

        $this->assertSame(0, $this->sends);
        $this->assertSame(4, $this->deliveryStatus($campaignId, $userId));
        $this->assertSame('consent_revoked', $this->deliveryError($campaignId, $userId));
        $this->assertSame(0, (int) DB::table('notification_campaigns')->where('id', $campaignId)->value('failure_count'));
        $this->assertSame(0, (int) DB::table('notification_campaigns')->where('id', $campaignId)->value('success_count'));
    }

    public function test_consent_is_checked_again_immediately_before_send(): void
    {
        $this->fakeFcm(200, ['name' => 'projects/netcellpay-fe31a/messages/1']);
        $userId = $this->user(6, 1, 'late-revoke-token-iiiiiiii');
        NotificationCampaignSender::$beforeSend = function (int $id) use ($userId): void {
            if ($id === $userId) {
                DB::table('users')->where('id', $id)->update(['promotional_push_consent' => 0]);
            }
        };
        $campaignId = $this->campaign('user', $userId, null, 'offers');

        $this->sender()->deliver($campaignId);

        $this->assertSame(0, $this->sends);
        $this->assertSame(4, $this->deliveryStatus($campaignId, $userId));
    }

    public function test_second_connection_cannot_lock_same_recipient(): void
    {
        $sender = $this->sender();
        $this->assertTrue($sender->acquireRecipientLock(990001, 990001));
        $pdo = new \PDO(
            'mysql:host=127.0.0.1;port='.(int) config('database.connections.mysql.port').';dbname=netcell_recharge',
            (string) config('database.connections.mysql.username'),
            (string) config('database.connections.mysql.password')
        );
        try {
            $got = (int) $pdo->query("SELECT GET_LOCK('nc990001_990001', 1)")->fetchColumn();
            $this->assertSame(0, $got);
        } finally {
            $sender->releaseRecipientLock(990001, 990001);
            $pdo->query("SELECT RELEASE_LOCK('nc990001_990001')");
        }
    }

    public function test_counters_match_delivery_rows(): void
    {
        $calls = 0;
        FcmHttpV1Service::fakeHttp(function (string $url, string $body) use (&$calls) {
            $calls++;
            $decoded = json_decode($body, true);
            $token = (string) ($decoded['message']['token'] ?? '');
            if ($token === 'bad-counter-token') {
                return ['status' => 404, 'body' => json_encode(['error' => ['status' => 'UNREGISTERED']])];
            }

            return ['status' => 200, 'body' => json_encode(['name' => 'projects/netcellpay-fe31a/messages/'.$calls])];
        });
        $ok = $this->user(5, 1, 'ok-counter-token');
        $bad = $this->user(5, 1, 'bad-counter-token');
        $none = $this->user(5, 1, null);
        $campaignId = $this->campaign('role', null, 5, 'recharge');

        $this->sender()->deliver($campaignId);
        $campaign = DB::table('notification_campaigns')->where('id', $campaignId)->first();

        $this->assertSame(3, (int) $campaign->total_recipients);
        $this->assertSame(1, (int) $campaign->success_count);
        $this->assertSame(1, (int) $campaign->failure_count);
        $this->assertSame(1, (int) $campaign->skipped_count);
        $this->assertSame('partially_failed', $campaign->status);
        $this->assertSame(1, $this->deliveryStatus($campaignId, $ok));
        $this->assertSame(3, $this->deliveryStatus($campaignId, $bad));
        $this->assertSame(2, $this->deliveryStatus($campaignId, $none));
        $this->assertTrue(DB::table('notification_campaign_events')->where('campaign_id', $campaignId)->where('action', 'completed')->exists());
    }

    public function test_existing_sms_and_whatsapp_rows_stay_untouched(): void
    {
        $userId = $this->user(6, 1, 'inbox-token-nnnnnnnn');
        $now = now();
        $smsId = $this->messageIds[] = DB::table('messages')->insertGetId([
            'user_id' => 1,
            'to_user_id' => $userId,
            'subject' => 'otp',
            'msg_source' => 'SMS',
            'template_id' => '0',
            'content' => 'sms-marker',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $whatsappId = $this->messageIds[] = DB::table('messages')->insertGetId([
            'user_id' => 1,
            'to_user_id' => $userId,
            'subject' => 'fund_receive',
            'msg_source' => 'WHATSAPP',
            'template_id' => '0',
            'content' => 'whatsapp-marker',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $secondWhatsapp = $this->messageIds[] = DB::table('messages')->insertGetId([
            'user_id' => 1,
            'to_user_id' => $userId,
            'subject' => 'fund_receive',
            'msg_source' => 'WHATSAPP',
            'template_id' => '0',
            'content' => 'whatsapp-marker-2',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->fakeFcm(200, ['name' => 'projects/netcellpay-fe31a/messages/1']);
        $campaignId = $this->campaign('user', $userId, null, 'wallet');
        $this->sender()->deliver($campaignId);

        foreach ([$smsId => 'sms-marker', $whatsappId => 'whatsapp-marker', $secondWhatsapp => 'whatsapp-marker-2'] as $id => $marker) {
            $row = DB::table('messages')->where('id', $id)->first();
            $this->assertSame($marker, $row->content);
            $this->assertNull($row->campaign_id);
            $this->assertSame(1, (int) $row->status);
        }
    }

    public function test_queue_dispatch_stores_job_without_the_token_when_driver_is_database(): void
    {
        config(['queue.default' => 'database']);
        $token = 'queued-token-oooooooo';
        $userId = $this->user(6, 1, $token);
        $campaignId = $this->campaign('user', $userId, null, 'wallet');
        $before = DB::table('jobs')->count();

        SendNotificationCampaign::dispatch($campaignId, false);
        $this->jobIds[] = (int) DB::table('jobs')->orderByDesc('id')->value('id');

        $this->assertSame($before + 1, DB::table('jobs')->count());
        $payload = (string) DB::table('jobs')->orderByDesc('id')->value('payload');
        $this->assertStringContainsString('SendNotificationCampaign', $payload);
        $this->assertStringNotContainsString($token, $payload);
        $this->assertSame(0, $this->messageRows($campaignId, $userId));
    }

    public function test_worker_give_up_keeps_partial_progress(): void
    {
        $sentId = $this->user(6, 1, 'kept-token-vvvvvvvv');
        $pendingId = $this->user(6, 1, 'pending-token-wwwwwwww');
        $campaignId = $this->campaign('role', null, 6, 'wallet');
        $now = now();
        foreach ([$sentId => 1, $pendingId => 0] as $userId => $status) {
            DB::table('messages')->insert([
                'user_id' => 1,
                'to_user_id' => $userId,
                'subject' => 'wallet title',
                'msg_source' => 'PUSH',
                'template_id' => '0',
                'content' => 'Hello wallet',
                'status' => $status,
                'campaign_id' => $campaignId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        (new SendNotificationCampaign($campaignId))->failed(new \RuntimeException('secret-db'));

        $this->assertSame('partially_failed', $this->campaignStatus($campaignId));
        $note = (string) DB::table('notification_campaign_events')->where('campaign_id', $campaignId)->where('action', 'completed')->value('note');
        $this->assertStringNotContainsString('secret-db', $note);
        $this->assertStringNotContainsString('pending-token-wwwwwwww', $note);
    }

    public function test_payload_includes_image_link_priority_and_ttl(): void
    {
        FcmHttpV1Service::fakeHttp(function (string $url, string $body) {
            $this->sends++;
            $this->bodies[] = $body;

            return ['status' => 200, 'body' => json_encode(['name' => 'projects/netcellpay-fe31a/messages/9'])];
        });
        $userId = $this->user(6, 1, 'payload-token-pppppppp');
        $campaignId = $this->campaign('user', $userId, null, 'security', now()->addHour(), 'normal', 'https://cdn.netcellpay.in/a.png', 'https://evil.example/phish');
        DB::table('notification_campaigns')->where('id', $campaignId)->update([
            'deep_link' => 'https://netcellpay.in/offers',
        ]);

        $this->sender()->deliver($campaignId);
        $message = json_decode($this->bodies[0], true)['message'];

        $this->assertNull($this->sender()->safeDeepLink('https://evil.example/phish'));
        $this->assertSame('/wallet', $this->sender()->safeDeepLink('/wallet'));
        $this->assertSame('NORMAL', $message['android']['priority']);
        $this->assertSame('https://cdn.netcellpay.in/a.png', $message['notification']['image']);
        $this->assertSame('https://netcellpay.in/offers', $message['data']['deep_link']);
        $this->assertNotEmpty($message['android']['ttl']);
        $this->assertStringNotContainsString('evil.example', $this->bodies[0]);
    }

    public function test_due_schedule_sends_and_a_future_schedule_waits(): void
    {
        $this->fakeFcm(200, ['name' => 'projects/netcellpay-fe31a/messages/1']);
        $userId = $this->user(6, 1, 'due-token-zzzzzzzz');
        $due = $this->campaign('user', $userId, null, 'maintenance');
        $future = $this->campaign('user', $userId, null, 'maintenance');
        DB::table('notification_campaigns')->where('id', $due)->update([
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
        ]);
        DB::table('notification_campaigns')->where('id', $future)->update([
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $this->artisan('notifications:dispatch-due')->assertExitCode(0);

        $this->assertSame('completed', $this->campaignStatus($due));
        $this->assertSame('scheduled', $this->campaignStatus($future));
        $this->assertSame(1, $this->sends);
        $this->assertSame(1, $this->deliveryStatus($due, $userId));
    }

    public function test_all_role_targets_skip_admin_roles(): void
    {
        $this->fakeFcm(200, ['name' => 'projects/netcellpay-fe31a/messages/1']);
        $admin = $this->user(1, 1, 'admin-token-qqqqqqqq');
        $staff = $this->user(2, 1, 'staff-token-rrrrrrrr');
        $retailer = $this->user(6, 1, 'all-token-ssssssss');
        $distributor = $this->user(5, 1, 'all-token-tttttttt');
        $master = $this->user(4, 1, 'all-token-uuuuuuuu');
        $campaignId = $this->campaign('all', null, null, 'maintenance');

        $this->sender()->deliver($campaignId);

        $this->assertSame(0, $this->messageRows($campaignId, $admin));
        $this->assertSame(0, $this->messageRows($campaignId, $staff));
        $this->assertSame(1, $this->deliveryStatus($campaignId, $retailer));
        $this->assertSame(1, $this->deliveryStatus($campaignId, $distributor));
        $this->assertSame(1, $this->deliveryStatus($campaignId, $master));
        $this->assertSame(3, (int) DB::table('notification_campaigns')->where('id', $campaignId)->value('total_recipients'));
    }

    private function fakeFcm(int $status, array $body): void
    {
        FcmHttpV1Service::fakeHttp(function () use ($status, $body) {
            $this->sends++;

            return ['status' => $status, 'body' => json_encode($body)];
        });
    }

    private function sender(): NotificationCampaignSender
    {
        return new NotificationCampaignSender();
    }

    private function user(int $roleId, int $consent, ?string $token): int
    {
        $id = (int) DB::table('users')->insertGetId([
            'mobile_number' => '9'.random_int(100000000, 999999999),
            'password' => 'campaign-test',
            'role_id' => $roleId,
            'status' => 1,
            'deleted_at' => 0,
            'promotional_push_consent' => $consent,
            'android_fcm_token' => $token,
            'fcm_token' => $token,
            'device_token' => $token,
            'first_name' => 'CampaignTest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->userIds[] = $id;
        NotificationCampaignSender::$onlyUserIds = $this->userIds;

        return $id;
    }

    private function campaign(string $target, ?int $userId, ?int $roleId, string $category, $expiresAt = null, string $priority = 'high', ?string $image = null, ?string $deepLink = null): int
    {
        $id = (int) DB::table('notification_campaigns')->insertGetId([
            'campaign_uuid' => (string) Str::uuid(),
            'title' => '<b>'.$category.' title</b>',
            'message' => 'Hello '.$category,
            'category' => $category,
            'image_url' => $image,
            'deep_link' => $deepLink,
            'priority' => $priority,
            'expires_at' => $expiresAt,
            'target_type' => $target,
            'target_role_id' => $roleId,
            'target_user_id' => $userId,
            'status' => 'processing',
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

    private function deliveryStatus(int $campaignId, int $userId): int
    {
        return (int) DB::table('messages')->where('campaign_id', $campaignId)->where('to_user_id', $userId)->value('status');
    }

    private function deliveryError(int $campaignId, int $userId): ?string
    {
        return DB::table('messages')->where('campaign_id', $campaignId)->where('to_user_id', $userId)->value('delivery_error');
    }

    private function messageRows(int $campaignId, int $userId): int
    {
        return (int) DB::table('messages')->where('campaign_id', $campaignId)->where('to_user_id', $userId)->count();
    }

    private function campaignStatus(int $campaignId): string
    {
        return (string) DB::table('notification_campaigns')->where('id', $campaignId)->value('status');
    }
}
