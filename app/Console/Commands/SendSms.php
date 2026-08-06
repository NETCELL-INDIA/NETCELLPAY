<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use helpers;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendEmail;
class SendSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send_sms_every_minutes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Message Every 1 Minutes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
       DB::table('users')->update(['otp_limit'=>0]); 
       $res = DB::table('messages')
                ->where('status', 0)
                ->take(100)->get();
       $rows =  $res->count();
       //echo "<pre>";print_r($rows);
        for ($i = 0; $i < $rows; $i++) {
            $user = DB::table('users')->where('id', $res[$i]->to_user_id)->first(['mobile_number','email_address']);
            $msg_data = [
                'mobile_number' => $user->mobile_number,
                'content' => $res[$i]->content,
                'template_id' => $res[$i]->template_id,
            ];
            $email_tmp = DB::table('email_templates')->where('slug', $res[$i]->subject)->first(['subject','content','status']);
            
            Mail::to(strtolower($user->email_address))->queue(new SendEmail($email_tmp->subject,$res[$i]->content));
            
            $sms = helpers::sendWhatasappMsg($msg_data);
            
            DB::table('messages')->where('id', $res[$i]->id)->update([
                'status' =>  1,
            ]);
        }
        return 0;
    }
}
