<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Session;

class ExtraReportsController extends Controller
{
    public function billIndex()
    {
        return view('users.user-reports.simple-report', [
            'title' => 'Bill Payment Report',
            'listRoute' => 'billPaymentReportsList',
        ]);
    }

    public function billList(Request $post)
    {
        return $this->list($post, ['Bill Payment', 'Bill Pay']);
    }

    public function daybookIndex()
    {
        return view('users.user-reports.simple-report', [
            'title' => 'Daybook',
            'listRoute' => 'daybookReportsList',
        ]);
    }

    public function daybookList(Request $post)
    {
        return $this->list($post, null);
    }

    private function list(Request $post, ?array $types)
    {
        $userId = (int) Session::get('user_id');
        if ($userId <= 0) {
            return '<h4 class="text-center text-secondary my-3">Login required</h4>';
        }

        $from = ($post->from_date ?: Carbon::today()->format('Y-m-d')).' 00:00:00';
        $to = ($post->to_date ?: Carbon::today()->format('Y-m-d')).' 23:59:59';
        $page = max(1, (int) $post->page);
        $limit = min(50, max(1, (int) ($post->limit ?: 20)));

        $query = DB::table('reports')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$from, $to]);

        if ($types) {
            $query->whereIn('transaction_type', $types);
        }
        if ($post->filled('order_id')) {
            $query->where('order_id', $post->order_id);
        }
        if ($post->filled('number')) {
            $query->where('number', $post->number);
        }

        $rows = $query->orderByDesc('id')->offset(($page - 1) * $limit)->limit($limit)->get();
        if ($rows->isEmpty()) {
            return '<h4 class="text-center text-secondary my-3">No records</h4>';
        }

        $html = '<div class="table-responsive"><table class="table table-bordered table-sm"><thead><tr><th>Date</th><th>Type</th><th>Order</th><th>Number</th><th>Amount</th><th>Status</th><th>Balance</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr><td>'.e($row->created_at).'</td><td>'.e($row->transaction_type ?? '').'</td><td>'.e($row->order_id ?? '').'</td><td>'.e($row->number ?? '').'</td><td>'.e($row->amount ?? '').'</td><td>'.e($row->status ?? '').'</td><td>'.e($row->closing_balance ?? '').'</td></tr>';
        }
        $html .= '</tbody></table></div>';

        return $html;
    }
}
