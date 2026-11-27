<?php

namespace Tests\Feature;

use App\Services\FcmHttpV1Service;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationConsentTest extends TestCase
{
    /** @var array<int, int> */
    private array $userIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $host = (string) config('database.connections.mysql.host');
        $name = (string) config('database.connections.mysql.database');
        if ($host !== '127.0.0.1' || $name !== 'netcell_recharge') {
            $this->fail('Refusing consent tests against '.$host.'/'.$name);
        }
    }

    protected function tearDown(): void
    {
        FcmHttpV1Service::fakeHttp(null);
        if ($this->userIds !== []) {
            DB::table('notification_consent_logs')->whereIn('user_id', $this->userIds)->delete();
            DB::table('messages')->whereIn('to_user_id', $this->userIds)->delete();
            DB::table('users')->whereIn('id', $this->userIds)->where('password', 'consent-test')->delete();
        }
        parent::tearDown();
    }

    public function test_existing_users_stay_opted_out_and_new_users_default_to_zero(): void
    {
        $existing = DB::table('users')->whereIn('id', [1, 2, 3])->pluck('promotional_push_consent', 'id');
        foreach ($existing as $id => $value) {
            $this->assertSame(0, (int) $value, 'user '.$id);
        }

        $id = $this->user('consent-new-default');
        $this->assertSame(0, (int) DB::table('users')->where('id', $id)->value('promotional_push_consent'));
        $this->assertNull(DB::table('users')->where('id', $id)->value('promotional_push_consent_at'));

        foreach ($existing as $userId => $value) {
            $this->assertSame((int) $value, (int) DB::table('users')->where('id', $userId)->value('promotional_push_consent'));
        }
    }

    public function test_user_can_read_grant_and_revoke_without_duplicate_logs(): void
    {
        $owner = $this->user('consent-owner-key');
        $other = $this->user('consent-other-key');

        $this->getJson('/api/user/notification-preferences')->assertOk()->assertJsonPath('type', 'error');

        $this->call('GET', '/api/user/notification-preferences', [
            'login_key' => 'consent-owner-key',
            'user_id' => $owner,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.promotional_push_consent', false);

        $this->postJson('/api/user/notification-preferences', [
            'login_key' => 'consent-owner-key',
            'user_id' => $owner,
            'promotional_push_consent' => true,
            'target_user_id' => $other,
        ])->assertOk()
            ->assertJsonPath('data.promotional_push_consent', true);

        $this->postJson('/api/user/notification-preferences', [
            'login_key' => 'consent-owner-key',
            'user_id' => $owner,
            'promotional_push_consent' => true,
        ])->assertOk();

        $this->assertSame(1, DB::table('notification_consent_logs')->where('user_id', $owner)->count());
        $this->assertSame(0, (int) DB::table('users')->where('id', $other)->value('promotional_push_consent'));

        $this->postJson('/api/user/notification-preferences', [
            'login_key' => 'consent-owner-key',
            'user_id' => $owner,
            'promotional_push_consent' => false,
        ])->assertOk()->assertJsonPath('data.promotional_push_consent', false);

        $log = DB::table('notification_consent_logs')->where('user_id', $owner)->orderByDesc('id')->first();
        $this->assertSame('revoked', $log->action);
        $this->assertSame(1, (int) $log->previous_value);
        $this->assertSame(0, (int) $log->new_value);
        $this->assertSame('app', $log->source);
        $this->assertSame(2, DB::table('notification_consent_logs')->where('user_id', $owner)->count());
    }

    public function test_invalid_and_cross_user_updates_are_rejected(): void
    {
        $owner = $this->user('consent-cross-owner');
        $other = $this->user('consent-cross-other');
        DB::table('users')->where('id', $other)->update(['promotional_push_consent' => 0]);

        $this->postJson('/api/user/notification-preferences', [
            'login_key' => 'consent-cross-owner',
            'user_id' => $owner,
            'promotional_push_consent' => 'yes',
        ])->assertOk()->assertJsonPath('type', 'error');

        $this->postJson('/api/user/notification-preferences', [
            'login_key' => 'consent-cross-owner',
            'user_id' => $other,
            'promotional_push_consent' => true,
        ])->assertOk()->assertJsonPath('type', 'error');

        $this->assertSame(0, (int) DB::table('users')->where('id', $other)->value('promotional_push_consent'));
        $this->assertSame(0, DB::table('notification_consent_logs')->where('user_id', $other)->count());
    }

    public function test_recharge_style_push_does_not_require_promotional_consent(): void
    {
        $sends = 0;
        FcmHttpV1Service::fakeHttp(function () use (&$sends) {
            $sends++;

            return ['status' => 200, 'body' => json_encode(['name' => 'projects/netcellpay-fe31a/messages/1'])];
        });
        $userId = $this->user('consent-event-key');
        DB::table('users')->where('id', $userId)->update([
            'promotional_push_consent' => 0,
            'android_fcm_token' => str_repeat('e', 80),
            'fcm_token' => str_repeat('e', 80),
            'device_token' => str_repeat('e', 80),
        ]);

        $sent = \helpers::pushNotifyUser($userId, 'Recharge Successful', 'Recharge successful for Rs. 10.', [], 'phase2_consent_event');

        $this->assertTrue($sent);
        $this->assertSame(1, $sends);
        $this->assertSame(0, (int) DB::table('users')->where('id', $userId)->value('promotional_push_consent'));
    }

    private function user(string $loginKey): int
    {
        $id = (int) DB::table('users')->insertGetId([
            'mobile_number' => '8'.random_int(100000000, 999999999),
            'password' => 'consent-test',
            'login_key' => $loginKey,
            'role_id' => 6,
            'status' => 1,
            'deleted_at' => 0,
            'first_name' => 'ConsentTest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->userIds[] = $id;

        return $id;
    }
}
