<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserServiceLockController extends Controller
{
    public function __construct()
    {
        $this->ensure();
    }

    public static function ensure(): void
    {
        try {
            if (! Schema::hasTable('user_service_locks')) {
                Schema::create('user_service_locks', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->index();
                    $table->unsignedBigInteger('service_id')->index();
                    $table->unsignedTinyInteger('is_locked')->default(1);
                    $table->timestamps();
                    $table->unique(['user_id', 'service_id']);
                });
            }
        } catch (\Throwable $e) {
        }
    }

    public function index()
    {
        $services = DB::table('services')
            ->where(function ($w) {
                $w->whereNull('deleted_at')->orWhere('deleted_at', '!=', 1);
            })
            ->orderBy('id')
            ->get(['id', 'service_name']);

        return view('admin.users.service-lock', compact('services'));
    }

    public function load(Request $request)
    {
        $id = (int) $request->user_id;
        $user = DB::table('users')->where('id', $id)->first(['id', 'first_name', 'last_name', 'outlet_name', 'mobile_number']);
        if (! $user) {
            return response()->json(['type' => 'error', 'message' => 'User not found']);
        }
        $locked = [];
        if (Schema::hasTable('user_service_locks')) {
            $locked = DB::table('user_service_locks')
                ->where('user_id', $id)
                ->where('is_locked', 1)
                ->pluck('service_id')
                ->all();
        }
        $services = DB::table('services')
            ->where(function ($w) {
                $w->whereNull('deleted_at')->orWhere('deleted_at', '!=', 1);
            })
            ->orderBy('id')
            ->get(['id', 'service_name']);
        $rows = [];
        foreach ($services as $s) {
            $rows[] = [
                'id' => (int) $s->id,
                'name' => $s->service_name,
                'locked' => in_array((int) $s->id, array_map('intval', $locked), true),
            ];
        }

        return response()->json([
            'type' => 'success',
            'user' => [
                'id' => $user->id,
                'label' => trim($user->first_name.' '.$user->last_name).' / '.$user->outlet_name.' ('.$user->mobile_number.')',
            ],
            'services' => $rows,
        ]);
    }

    public function save(Request $request)
    {
        $this->ensure();
        $id = (int) $request->user_id;
        $user = DB::table('users')->where('id', $id)->first(['id']);
        if (! $user) {
            return response()->json(['type' => 'error', 'message' => 'User not found']);
        }
        $lockedIds = array_map('intval', (array) $request->locked);
        $services = DB::table('services')->pluck('id')->map(fn ($v) => (int) $v)->all();
        $old = Schema::hasTable('user_service_locks')
            ? DB::table('user_service_locks')->where('user_id', $id)->where('is_locked', 1)->pluck('service_id')->all()
            : [];

        DB::table('user_service_locks')->where('user_id', $id)->delete();
        foreach ($services as $sid) {
            if (in_array($sid, $lockedIds, true)) {
                DB::table('user_service_locks')->insert([
                    'user_id' => $id,
                    'service_id' => $sid,
                    'is_locked' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        AdminAudit::log('service_lock', 'user_service_lock', [
            'ref_type' => 'user',
            'ref_id' => $id,
            'old' => $old,
            'new' => $lockedIds,
        ]);

        return response()->json(['type' => 'success', 'message' => 'Service lock saved']);
    }
}
