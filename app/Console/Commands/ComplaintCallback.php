<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use helpers;
class ComplaintCallback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'complaint_callback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $res = DB::table('complaints as c')
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->where('c.path', 'Api')
            ->where('c.callback_status', 0)
            ->whereNotIn('c.status', ['Open', 'Under Review'])
            ->whereNotNull('u.complaint_callback_url')
            ->where('u.complaint_callback_url', '!=', '')
            ->orderBy('c.id')
            ->take(100)
            ->get([
                'c.id',
                'c.status',
                'c.decision_remark',
                'c.request_id',
                'c.decision_date',
                'u.complaint_callback_url',
            ]);

        foreach ($res as $row) {
            $base = trim((string) $row->complaint_callback_url);
            if ($base === '' || ! filter_var($base, FILTER_VALIDATE_URL) || helpers::isUnusableCallbackUrl($base)) {
                continue;
            }
            $query = http_build_query([
                'status' => (string) $row->status,
                'decision_remark' => (string) $row->decision_remark,
                'request_id' => (string) $row->request_id,
                'decision_date' => (string) $row->decision_date,
            ]);
            $url = $base.(str_contains($base, '?') ? '&' : '?').$query;
            $result = helpers::curl($url, 'GET', '', [], 'yes', 'USER_COMPLAINT_CALLBACK', (string) $row->request_id, true);
            $code = (int) ($result['code'] ?? 0);
            $err = trim((string) ($result['error'] ?? ''));
            if ($err === '' && $code >= 200 && $code < 300) {
                DB::table('complaints')->where('id', $row->id)->update([
                    'callback_status' => 1,
                ]);
            }
        }
        return 0;
    }
}
