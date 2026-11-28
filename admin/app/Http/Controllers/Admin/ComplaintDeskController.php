<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAudit;
use Carbon\Carbon;
use App\Services\AdminMenuService;
use App\Services\ComplaintDeskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplaintDeskController extends Controller
{
    public function dashboard(Request $request, ComplaintDeskService $desk)
    {
        $filters = $this->filters($request);

        return view('admin.support.complaint-dashboard', [
            'ready' => Schema::hasTable('complaints'),
            'counts' => Schema::hasTable('complaints') ? $desk->counts($filters) : [],
            'filters' => $filters,
            'categories' => $desk->schemaReady() ? $desk->categories() : [],
        ]);
    }

    public function index(Request $request, ComplaintDeskService $desk, string $queue = 'all')
    {
        $filters = $this->filters($request);
        $filters['queue'] = $queue;

        return view('admin.support.complaint-list', [
            'ready' => Schema::hasTable('complaints'),
            'queue' => $queue,
            'title' => $this->queueTitle($queue),
            'rows' => Schema::hasTable('complaints') ? $desk->page($filters) : null,
            'filters' => $filters,
            'categories' => $desk->schemaReady() ? $desk->categories() : [],
        ]);
    }

    public function show(int $id, ComplaintDeskService $desk)
    {
        $complaint = $desk->find($id);
        if (! $complaint) {
            return redirect()->route('complaintDeskAll')->with('error', 'That complaint was not found.');
        }

        return view('admin.support.complaint-show', [
            'complaint' => $complaint,
            'sections' => $desk->detailSections($complaint),
            'messages' => $desk->messages($id, true),
            'history' => $desk->history($id),
            'attachments' => $desk->attachments($id, true),
            'feedback' => $desk->feedback($id),
            'staff' => $desk->staffOptions(),
            'canReopen' => (int) session('role_id') === AdminMenuService::SUPERADMIN_ROLE_ID
                || AdminMenuService::can('support.reopen'),
        ]);
    }

    public function assign(Request $request, int $id, ComplaintDeskService $desk)
    {
        return $this->act($id, $desk, function ($complaint) use ($request, $desk) {
            $error = $desk->assign($complaint, (int) $request->input('staff_id'), (int) session('user_id'), trim((string) $request->input('remarks')));
            if ($error === '') {
                $this->audit('assign', $complaint->id, 'Assigned to staff '.$request->input('staff_id'));
            }

            return $error;
        });
    }

    public function priority(Request $request, int $id, ComplaintDeskService $desk)
    {
        return $this->act($id, $desk, function ($complaint) use ($request, $desk) {
            $error = $desk->setPriority($complaint, (string) $request->input('priority'));
            if ($error === '') {
                $this->audit('priority', $complaint->id, 'Priority '.$request->input('priority'));
            }

            return $error;
        });
    }

    public function status(Request $request, int $id, ComplaintDeskService $desk)
    {
        return $this->act($id, $desk, function ($complaint) use ($request, $desk) {
            $before = DB::table('reports')->where('id', (int) $complaint->report_id)->value('status');
            $wallet = DB::table('users')->where('id', (int) $complaint->user_id)->value('wallet_balance');
            $error = $desk->changeStatus($complaint, (string) $request->input('status'), (int) session('user_id'), trim((string) $request->input('remarks')));
            if ($error === '') {
                $this->assertUntouched($complaint, $before, $wallet);
                $this->audit('status', $complaint->id, (string) $request->input('status'));
            }

            return $error;
        });
    }

    public function reply(Request $request, int $id, ComplaintDeskService $desk)
    {
        return $this->messageAction($request, $id, $desk, 'customer', false);
    }

    public function requestInfo(Request $request, int $id, ComplaintDeskService $desk)
    {
        return $this->act($id, $desk, function ($complaint) use ($request, $desk) {
            $body = trim((string) $request->input('body'));
            $result = $desk->addMessage($complaint, (int) session('user_id'), 'admin', $body, 'customer', $request->file('file'));
            if ($result['error'] !== '') {
                return $result['error'];
            }
            $error = $desk->changeStatus($complaint, 'WAITING FOR CUSTOMER', (int) session('user_id'), 'More information requested');
            if ($error === '') {
                $this->audit('request-info', $complaint->id, 'Waiting for customer');
            }

            return $error;
        });
    }

    public function note(Request $request, int $id, ComplaintDeskService $desk)
    {
        return $this->messageAction($request, $id, $desk, 'internal', false);
    }

    public function attachment(Request $request, int $id, ComplaintDeskService $desk)
    {
        return $this->messageAction($request, $id, $desk, (string) $request->input('visibility', 'internal'), true);
    }

    public function resolve(Request $request, int $id, ComplaintDeskService $desk)
    {
        return $this->statusShortcut($request, $id, $desk, 'RESOLVED');
    }

    public function reject(Request $request, int $id, ComplaintDeskService $desk)
    {
        return $this->statusShortcut($request, $id, $desk, 'REJECTED');
    }

    public function close(Request $request, int $id, ComplaintDeskService $desk)
    {
        return $this->statusShortcut($request, $id, $desk, 'CLOSED');
    }

    public function reopen(Request $request, int $id, ComplaintDeskService $desk)
    {
        if ((int) session('role_id') !== AdminMenuService::SUPERADMIN_ROLE_ID && ! AdminMenuService::can('support.reopen')) {
            return redirect()->route('complaintDeskShow', $id)->with('error', 'You are not allowed to reopen this complaint.');
        }

        return $this->statusShortcut($request, $id, $desk, 'UNDER REVIEW');
    }

    public function download(int $id, ComplaintDeskService $desk)
    {
        if (! $desk->schemaReady()) {
            abort(404);
        }
        $file = DB::table('complaint_attachments')->where('id', $id)->first();
        if (! $file || ! str_starts_with((string) $file->stored_path, 'complaint-attachments/')) {
            abort(404);
        }

        return Storage::disk('local')->download($file->stored_path, $file->original_name);
    }

    public function reports(Request $request, ComplaintDeskService $desk)
    {
        $filters = $this->periodFilters($request);
        $rows = $desk->schemaReady() ? $desk->reportRows($filters) : collect();
        $summary = [
            'category' => [],
            'operator' => [],
            'provider' => [],
            'staff' => [],
        ];
        $minutes = [];
        foreach ($rows as $row) {
            $this->bump($summary['category'], $row->category ?: ($row->subject ?: 'Not recorded'));
            $this->bump($summary['operator'], $row->provider_name ?: 'Not recorded');
            $this->bump($summary['provider'], $row->api_name ?: 'Not recorded');
            $this->bump($summary['staff'], $row->assigned_to ? 'Staff '.$row->assigned_to : 'Unassigned');
            if (! empty($row->resolved_at) && ! empty($row->created_at)) {
                $minutes[] = Carbon::parse($row->created_at)->diffInMinutes(Carbon::parse($row->resolved_at));
            }
        }

        return view('admin.support.complaint-reports', [
            'ready' => $desk->schemaReady(),
            'categories' => $desk->schemaReady() ? $desk->categories() : [],
            'filters' => $filters,
            'total' => $rows->count(),
            'summary' => $summary,
            'average_minutes' => $minutes === [] ? null : (int) round(array_sum($minutes) / count($minutes)),
        ]);
    }

    public function export(Request $request, ComplaintDeskService $desk): StreamedResponse
    {
        $filters = $this->periodFilters($request);
        $rows = $desk->schemaReady() ? $desk->reportRows($filters) : collect();

        return response()->streamDownload(function () use ($rows, $desk) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Ticket', 'Created', 'User', 'Mobile', 'Subject', 'Status', 'Priority', 'Operator', 'API', 'Transaction status', 'Amount']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->request_id,
                    $row->created_at,
                    $row->outlet_name ?: $row->first_name,
                    $row->mobile_number,
                    $row->subject,
                    $desk->workflowStatus($row->status),
                    $row->priority,
                    $row->provider_name,
                    $row->api_name,
                    $row->txn_status,
                    $row->total_amount,
                ]);
            }
            fclose($out);
        }, 'complaint-report.csv', ['Content-Type' => 'text/csv']);
    }

    private function statusShortcut(Request $request, int $id, ComplaintDeskService $desk, string $status)
    {
        $request->merge(['status' => $status]);

        return $this->status($request, $id, $desk);
    }

    private function messageAction(Request $request, int $id, ComplaintDeskService $desk, string $visibility, bool $fileRequired)
    {
        return $this->act($id, $desk, function ($complaint) use ($request, $desk, $visibility, $fileRequired) {
            if ($fileRequired && ! $request->file('file')) {
                return 'Choose a JPG, PNG, or PDF up to 2 MB.';
            }
            $result = $desk->addMessage(
                $complaint,
                (int) session('user_id'),
                'admin',
                (string) $request->input('body'),
                $visibility,
                $request->file('file')
            );
            if ($result['error'] === '') {
                $this->audit($visibility === 'internal' ? 'note' : 'reply', $complaint->id, $visibility);
            }

            return $result['error'];
        });
    }

    private function act(int $id, ComplaintDeskService $desk, callable $callback)
    {
        $complaint = $desk->schemaReady() ? $desk->find($id) : null;
        if (! $complaint) {
            return redirect()->route('complaintDeskAll')->with('error', 'That complaint was not found.');
        }
        $error = $callback($complaint);

        return redirect()->route('complaintDeskShow', $id)->with($error === '' ? 'status' : 'error', $error === '' ? 'Complaint updated. Wallet and recharge status were not changed.' : $error);
    }

    private function assertUntouched(object $complaint, $beforeStatus, $beforeWallet): void
    {
        if (! empty($complaint->report_id)) {
            $after = DB::table('reports')->where('id', (int) $complaint->report_id)->value('status');
            if ((string) $after !== (string) $beforeStatus) {
                DB::table('reports')->where('id', (int) $complaint->report_id)->update(['status' => $beforeStatus]);
            }
        }
        if (! empty($complaint->user_id) && $beforeWallet !== null) {
            $afterWallet = DB::table('users')->where('id', (int) $complaint->user_id)->value('wallet_balance');
            if ((string) $afterWallet !== (string) $beforeWallet) {
                DB::table('users')->where('id', (int) $complaint->user_id)->update(['wallet_balance' => $beforeWallet]);
            }
        }
    }

    private function audit(string $action, int $id, string $remark): void
    {
        AdminAudit::log('complaint', $action, [
            'ref_type' => 'complaint',
            'ref_id' => $id,
            'remark' => $remark,
        ]);
    }

    private function bump(array &$bucket, string $key): void
    {
        $bucket[$key] = ($bucket[$key] ?? 0) + 1;
    }

    private function periodFilters(Request $request): array
    {
        $filters = $this->filters($request);
        if ($filters['from'] === '' && $filters['to'] === '') {
            $today = Carbon::today();
            if ($filters['period'] === 'weekly') {
                $filters['from'] = $today->copy()->startOfWeek()->toDateString();
            } elseif ($filters['period'] === 'monthly') {
                $filters['from'] = $today->copy()->startOfMonth()->toDateString();
            } elseif ($filters['period'] === 'daily') {
                $filters['from'] = $today->toDateString();
            }
            if ($filters['from'] !== '') {
                $filters['to'] = $today->toDateString();
            }
        }

        return $filters;
    }

    private function filters(Request $request): array
    {
        return [
            'from' => trim((string) $request->query('from', '')),
            'to' => trim((string) $request->query('to', '')),
            'category' => trim((string) $request->query('category', '')),
            'staff' => (int) $request->query('staff', 0),
            'period' => trim((string) $request->query('period', '')),
        ];
    }

    private function queueTitle(string $queue): string
    {
        return [
            'all' => 'All Complaints',
            'mobile' => 'Mobile Recharge Complaints',
            'dth' => 'DTH Complaints',
            'new' => 'New Complaints',
            'pending' => 'Pending Complaints',
            'assigned' => 'Assigned Complaints',
            'waiting' => 'Waiting for Customer',
            'resolved' => 'Resolved Complaints',
            'rejected' => 'Rejected Complaints',
        ][$queue] ?? 'Complaints';
    }
}
