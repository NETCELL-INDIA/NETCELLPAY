<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DenominationBlockController extends Controller
{
    public function operator()
    {
        return $this->page('operator');
    }

    public function user()
    {
        return $this->page('user');
    }

    public function list(Request $request)
    {
        $this->ensureTables();
        $mode = $this->mode($request);
        $table = $this->table($mode);
        $query = DB::table($table.' as b')
            ->leftJoin('providers as p', 'p.id', '=', 'b.provider_id')
            ->leftJoin('services as s', 's.id', '=', 'b.service_id')
            ->select('b.*', 'p.provider_name', 's.service_name');
        if ($mode === 'user') {
            $query->leftJoin('users as u', 'u.id', '=', 'b.user_id')
                ->addSelect('u.mobile_number', 'u.outlet_name', 'u.first_name');
        }
        $rows = $query->orderByDesc('b.id')->limit(300)->get();

        return view('admin.system.denomination-block-rows', [
            'rows' => $rows,
            'mode' => $mode,
        ]);
    }

    public function save(Request $request)
    {
        $this->ensureTables();
        $mode = $this->mode($request);
        $amounts = $this->normalizeAmounts((string) $request->input('amounts'));
        $status = (int) $request->input('status') === 1 ? 1 : 0;
        $providerId = (int) $request->input('provider_id');
        $editId = (int) $request->input('edit_id');

        if ($amounts === null) {
            return response()->json(['type' => 'error', 'message' => 'Enter amounts like 10,20,239']);
        }
        $provider = DB::table('providers')->where('id', $providerId)->first();
        if (! $provider) {
            return response()->json(['type' => 'error', 'message' => 'Select an operator']);
        }

        $userId = 0;
        if ($mode === 'user') {
            $mobile = preg_replace('/\D+/', '', (string) $request->input('mobile_number')) ?? '';
            if (strlen($mobile) === 12 && str_starts_with($mobile, '91')) {
                $mobile = substr($mobile, 2);
            }
            $user = DB::table('users')->where('mobile_number', $mobile)->whereNotIn('role_id', [1, 2])->first();
            if (! $user) {
                return response()->json(['type' => 'error', 'message' => 'Retailer mobile number was not found']);
            }
            $userId = (int) $user->id;
        }

        $table = $this->table($mode);
        $payload = [
            'service_id' => (int) ($provider->service_id ?? 0),
            'provider_id' => $providerId,
            'amounts' => $amounts,
            'status' => $status,
            'updated_at' => Carbon::now(),
        ];
        if ($mode === 'user') {
            $payload['user_id'] = $userId;
        }

        if ($editId > 0) {
            $ok = DB::table($table)->where('id', $editId)->update($payload);
        } else {
            $exists = DB::table($table)->where('provider_id', $providerId);
            if ($mode === 'user') {
                $exists->where('user_id', $userId);
            }
            if ($exists->exists()) {
                return response()->json(['type' => 'error', 'message' => 'This block already exists. Edit that row.']);
            }
            $payload['created_at'] = Carbon::now();
            $ok = DB::table($table)->insert($payload);
        }

        return response()->json([
            'type' => $ok ? 'success' : 'error',
            'message' => $ok ? 'Saved' : 'Could not save',
        ]);
    }

    public function delete(Request $request)
    {
        $this->ensureTables();
        $ok = DB::table($this->table($this->mode($request)))->where('id', (int) $request->input('id'))->delete();

        return response()->json([
            'type' => $ok ? 'success' : 'error',
            'message' => $ok ? 'Deleted' : 'Could not delete',
        ]);
    }

    private function page(string $mode)
    {
        $this->ensureTables();
        $services = Schema::hasTable('services')
            ? DB::table('services')->orderBy('service_name')->get(['id', 'service_name'])
            : collect();

        return view('admin.system.denomination-block', [
            'mode' => $mode,
            'title' => $mode === 'user' ? 'User Operator Denomination Block' : 'Operator Denomination Block',
            'services' => $services,
        ]);
    }

    private function mode(Request $request): string
    {
        return $request->input('mode') === 'user' ? 'user' : 'operator';
    }

    private function table(string $mode): string
    {
        return $mode === 'user' ? 'user_operator_denomination_blocks' : 'operator_denomination_blocks';
    }

    private function normalizeAmounts(string $raw): ?string
    {
        $out = [];
        foreach (preg_split('/\s*,\s*/', $raw) as $part) {
            $part = trim((string) $part);
            if ($part === '' || ! is_numeric($part)) {
                return null;
            }
            $n = round((float) $part, 2);
            if ($n <= 0 || $n > 100000) {
                return null;
            }
            $out[] = rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
        }
        $out = array_values(array_unique($out));
        if ($out === [] || count($out) > 40) {
            return null;
        }

        return implode(',', $out);
    }

    private function ensureTables(): void
    {
        if (! Schema::hasTable('operator_denomination_blocks')) {
            Schema::create('operator_denomination_blocks', function ($table) {
                $table->id();
                $table->unsignedBigInteger('service_id')->default(0);
                $table->unsignedBigInteger('provider_id')->index();
                $table->string('amounts', 500);
                $table->unsignedTinyInteger('status')->default(1)->index();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('user_operator_denomination_blocks')) {
            Schema::create('user_operator_denomination_blocks', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('service_id')->default(0);
                $table->unsignedBigInteger('provider_id')->index();
                $table->string('amounts', 500);
                $table->unsignedTinyInteger('status')->default(1)->index();
                $table->timestamps();
                $table->unique(['user_id', 'provider_id']);
            });
        }
    }
}
