<?php

namespace Tests\Feature;

use App\Services\ComplaintDeskService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ComplaintDeskTest extends TestCase
{
    /** @var array<int, int> */
    private array $complaintIds = [];

    private $reportStatus;

    private $walletBalance;

    private int $reportId = 0;

    private int $userId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $host = (string) config('database.connections.mysql.host');
        $name = (string) config('database.connections.mysql.database');
        if ($host !== '127.0.0.1' || $name !== 'netcell_recharge') {
            $this->fail('Refusing complaint tests against '.$host.'/'.$name);
        }
        ComplaintDeskService::$pushSender = function () {
            return false;
        };
    }

    protected function tearDown(): void
    {
        if ($this->complaintIds !== []) {
            DB::table('complaint_messages')->whereIn('complaint_id', $this->complaintIds)->delete();
            DB::table('complaint_attachments')->whereIn('complaint_id', $this->complaintIds)->delete();
            DB::table('complaint_assignments')->whereIn('complaint_id', $this->complaintIds)->delete();
            DB::table('complaint_status_history')->whereIn('complaint_id', $this->complaintIds)->delete();
            DB::table('complaint_feedback')->whereIn('complaint_id', $this->complaintIds)->delete();
            DB::table('complaints')->whereIn('id', $this->complaintIds)->delete();
        }
        DB::table('messages')->where('subject', 'like', 'Complaint CMPTEST%')->delete();
        if ($this->reportId > 0) {
            DB::table('reports')->where('id', $this->reportId)->update(['status' => $this->reportStatus]);
        }
        if ($this->userId > 0) {
            DB::table('users')->where('id', $this->userId)->update(['wallet_balance' => $this->walletBalance]);
        }
        ComplaintDeskService::$pushSender = null;
        parent::tearDown();
    }

    public function test_guest_cannot_open_the_dashboard(): void
    {
        $this->get('/admin/support/complaints/dashboard')->assertRedirect();
    }

    public function test_status_change_is_audited_and_does_not_touch_money(): void
    {
        $admin = $this->adminUser();
        $id = $this->insertComplaint('Recharge Failed');
        $this->actingAsAdmin($admin)
            ->get('/admin/support/complaints/dashboard')
            ->assertOk()
            ->assertSee('Total Complaints');

        $this->actingAsAdmin($admin)
            ->post('/admin/support/complaints/ticket/'.$id.'/reject', ['remarks' => ''])
            ->assertRedirect();
        $this->assertSame('Open', DB::table('complaints')->where('id', $id)->value('status'));

        $this->actingAsAdmin($admin)
            ->post('/admin/support/complaints/ticket/'.$id.'/status', [
                'status' => 'IN PROGRESS',
                'remarks' => 'Checking the provider response',
            ])
            ->assertRedirect();

        $this->assertSame('IN PROGRESS', DB::table('complaints')->where('id', $id)->value('status'));
        $history = DB::table('complaint_status_history')->where('complaint_id', $id)->first();
        $this->assertSame('NEW', $history->previous_status);
        $this->assertSame('IN PROGRESS', $history->new_status);
        $this->assertSame((int) $admin->id, (int) $history->admin_id);
        $this->assertSame((string) $this->reportStatus, (string) DB::table('reports')->where('id', $this->reportId)->value('status'));
        $this->assertSame((string) $this->walletBalance, (string) DB::table('users')->where('id', $this->userId)->value('wallet_balance'));

        $this->actingAsAdmin($admin)
            ->post('/admin/support/complaints/ticket/'.$id.'/note', ['body' => 'Internal only note'])
            ->assertRedirect();
        $this->assertSame(0, DB::table('complaint_messages')->where('complaint_id', $id)->where('visibility', 'customer')->count());
        $this->assertSame(1, DB::table('complaint_messages')->where('complaint_id', $id)->where('visibility', 'internal')->count());

        $this->actingAsAdmin($admin)
            ->post('/admin/support/complaints/ticket/'.$id.'/attachment', [
                'visibility' => 'internal',
                'body' => 'script',
                'file' => UploadedFile::fake()->create('bad.php', 10, 'application/x-php'),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame(0, DB::table('complaint_attachments')->where('complaint_id', $id)->count());
    }

    public function test_retailer_cannot_reopen_a_complaint(): void
    {
        $id = $this->insertComplaint('DTH Complaint');
        $retailer = DB::table('users')->where('role_id', 6)->where('status', 1)->first();
        if (! $retailer) {
            $this->markTestSkipped('No active retailer.');
        }
        $this->actingAsAdmin($retailer)
            ->post('/admin/support/complaints/ticket/'.$id.'/reopen')
            ->assertRedirect();
        $this->assertSame('Open', DB::table('complaints')->where('id', $id)->value('status'));
    }

    public function test_report_export_includes_the_ticket(): void
    {
        $id = $this->insertComplaint('Refund Not Received');
        $ticket = DB::table('complaints')->where('id', $id)->value('request_id');
        $response = $this->actingAsAdmin($this->adminUser())
            ->get('/admin/support/complaints/reports/export?from=1970-01-01&to=2037-12-31');
        $response->assertOk();
        $this->assertStringContainsString($ticket, $response->streamedContent());
    }

    private function insertComplaint(string $subject): int
    {
        $report = DB::table('reports')->whereNotNull('service_id')->orderByDesc('id')->first();
        if (! $report) {
            $this->fail('No recharge report is available for the complaint fixture.');
        }
        $this->reportId = (int) $report->id;
        $this->reportStatus = $report->status;
        $this->userId = (int) $report->user_id;
        $this->walletBalance = DB::table('users')->where('id', $this->userId)->value('wallet_balance');
        $nextId = (int) DB::table('complaints')->max('id') + 1;
        DB::table('complaints')->insert([
            'id' => $nextId,
            'user_id' => $this->userId,
            'service_id' => $report->service_id,
            'order_id' => $report->order_id,
            'report_id' => $report->id,
            'request_id' => 'CMPTEST'.substr(md5($subject.microtime(true)), 0, 12),
            'subject' => $subject,
            'status' => 'Open',
            'path' => 'Test',
            'callback_status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->complaintIds[] = $nextId;

        return $nextId;
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
}
