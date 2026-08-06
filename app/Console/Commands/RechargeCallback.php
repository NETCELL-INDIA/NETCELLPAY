<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use Illuminate\Support\Facades\DB;
use helpers;
class RechargeCallback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recharge_callback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recharge Call Back Hit';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $res = DB::table('reports')->where('path','Api')
                ->where('transaction_type','Recharge')
                ->where('callback_status','0')
                ->where('status','!=','Pending')->take(100)->get();
       $rows =  $res->count();
        for ($i = 0; $i < $rows; $i++) {
            $user = DB::table('users')->where('id', $res[$i]->user_id)->first(['callback_url']);
            $url = $user->callback_url . "?request_order_id=" . $res[$i]->request_order_id . "&status=" . $res[$i]->status . "&amount=" . $res[$i]->total_amount . "&order_id=" . $res[$i]->order_id . "&operator_id=" . $res[$i]->operator_id;
            $order_id = $res[$i]->order_id;
            $header = [];
            $result = helpers::curl($url, "GET", "", $header, "yes", "USER_RECHARGE_CALLBACK", $order_id);
            //echo "<pre>";print_r($result);
            DB::table('reports')->where('id', $res[$i]->id)->update([
                'api_partner_call_back_url' =>  $url,
                'api_partner_callback_response' =>  $result['response'],
                'callback_status' =>  1,
            ]);
        }
        return 0;
    }
}
