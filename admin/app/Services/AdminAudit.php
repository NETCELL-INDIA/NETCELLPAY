<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Session;

class AdminAudit
{
    public static function ensureTable(): void
    {
        try {
            if (Schema::hasTable('admin_audit_logs')) {
                return;
            }
            Schema::create('admin_audit_logs', function ($table) {
                $table->id();
                $table->unsignedBigInteger('admin_id')->default(0)->index();
                $table->string('admin_name', 120)->nullable();
                $table->string('module', 40)->index();
                $table->string('action', 80);
                $table->string('ref_type', 40)->nullable()->index();
                $table->string('ref_id', 64)->nullable()->index();
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->string('remark', 255)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        } catch (\Throwable $e) {
        }
    }

    public static function log(string $module, string $action, array $opt = []): void
    {
        try {
            self::ensureTable();
            if (! Schema::hasTable('admin_audit_logs')) {
                return;
            }
            $adminId = (int) Session::get('user_id');
            $name = '';
            if ($adminId > 0) {
                $u = DB::table('users')->where('id', $adminId)->first(['first_name', 'outlet_name']);
                $name = trim((string) (($u->first_name ?? '').' '.($u->outlet_name ?? '')));
            }
            $encode = function ($v) {
                if ($v === null || $v === '') {
                    return null;
                }
                if (is_string($v)) {
                    return mb_substr($v, 0, 4000);
                }

                return mb_substr(json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 4000);
            };
            DB::table('admin_audit_logs')->insert([
                'admin_id' => $adminId,
                'admin_name' => mb_substr($name !== '' ? $name : 'Admin', 0, 120),
                'module' => mb_substr($module, 0, 40),
                'action' => mb_substr($action, 0, 80),
                'ref_type' => isset($opt['ref_type']) ? mb_substr((string) $opt['ref_type'], 0, 40) : null,
                'ref_id' => isset($opt['ref_id']) ? mb_substr((string) $opt['ref_id'], 0, 64) : null,
                'old_value' => $encode($opt['old'] ?? null),
                'new_value' => $encode($opt['new'] ?? null),
                'ip_address' => request()->ip(),
                'remark' => isset($opt['remark']) ? mb_substr((string) $opt['remark'], 0, 255) : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }
    }
}
