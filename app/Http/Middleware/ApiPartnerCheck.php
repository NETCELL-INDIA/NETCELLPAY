<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class ApiPartnerCheck
{
    
    public function handle(Request $post, Closure $next)
    {
        $rules = array(
            'api_key' => 'required',
        );

        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json(array(
                'type' => 'error',  
                'message' => $error
            ));
        }
        $user = DB::table('users')->where("api_key",$post->api_key)->whereNotIn("role_id",[1,2])->first();
        if(!$user){
            return response()->json(array(
                'type' => 'error',  
                'message' => "invaild api key"
            )); 
        }

        $configuredIp = trim((string) $user->ip_address);
        $clientIp = (string) \helpers::getIp();

        if ($configuredIp === '' || in_array($configuredIp, ['0.0.0.0', '1.1.1.1'], true)) {
            self::recordRejectedIp((int) $user->id, $clientIp, 'IP not approved');
            return response()->json(array(
                'type' => 'error',
                'message' => "ip not approved, contact admin to approve your ip " . $clientIp,
            ));
        }

        if (!self::isIpAllowed($configuredIp, $clientIp)) {
            self::recordRejectedIp((int) $user->id, $clientIp, 'IP not in whitelist');
            return response()->json(array(
                'type' => 'error',
                'message' => "invaild ip address your ip is " . $clientIp,
            ));
        }
        // //return "shiba";
        return $next($post);
    }

    protected static function isIpAllowed(string $configuredIp, string $clientIp): bool
    {
        if ($configuredIp === '*') {
            return true;
        }

        $allowed = array_filter(array_map('trim', explode(',', $configuredIp)));
        return in_array($clientIp, $allowed, true);
    }

    protected static function recordRejectedIp(int $userId, string $ip, string $reason): void
    {
        if ($userId <= 0 || $ip === '') {
            return;
        }
        try {
            if (! Schema::hasTable('api_ip_rejects')) {
                Schema::create('api_ip_rejects', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->index();
                    $table->string('ip_address', 64);
                    $table->string('reason', 80);
                    $table->timestamp('created_at')->nullable();
                });
            }
            $recent = DB::table('api_ip_rejects')
                ->where('user_id', $userId)
                ->where('ip_address', $ip)
                ->where('created_at', '>=', now()->subMinutes(15))
                ->exists();
            if ($recent) {
                return;
            }
            DB::table('api_ip_rejects')->insert([
                'user_id' => $userId,
                'ip_address' => mb_substr($ip, 0, 64),
                'reason' => mb_substr($reason, 0, 80),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }
    }
}
