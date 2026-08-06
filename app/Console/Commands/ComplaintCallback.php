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
        $res = DB::table('complaints')->where('path','Api')
                ->where('callback_status', 0)
                ->whereNotIn('status',['Open','Under Review'])->take(100)->get();
       $rows =  $res->count();
       //echo "<pre>";print_r($rows);
        for ($i = 0; $i < $rows; $i++) {
            $user = DB::table('users')->where('id', $res[$i]->user_id)->first(['complaint_callback_url']);
            $url = $user->complaint_callback_url . "?status=" . $res[$i]->status . "&decision_remark=" . $res[$i]->decision_remark . "&request_id=" . $res[$i]->request_id . "&decision_date=" . $res[$i]->decision_date;
            $order_id = $res[$i]->request_id;
            $header = [];
            $result = helpers::curl($url, "GET", "", $header, "yes", "USER_COMPLAINT_CALLBACK", $order_id);
            //echo "<pre>";print_r($result);
            DB::table('complaints')->where('id', $res[$i]->id)->update([
                'callback_status' =>  1,
            ]);
        }
        return 0;
    }
}
