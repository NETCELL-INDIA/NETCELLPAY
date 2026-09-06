<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ServiceController extends Controller
{
    public function index(Request $post)
    {
        $this->ensureServiceColumns();

        return view('admin.system.services');
    }

    public function fetchAll(Request $post)
    {
        $this->ensureServiceColumns();

        $query = DB::table('services');
        if (Schema::hasColumn('services', 'deleted_at')) {
            $query->where(function ($q) {
                $q->where('deleted_at', '!=', 1)->orWhereNull('deleted_at');
            });
        }

        $services = $query->orderBy('sort_order')->orderBy('id')->get();
        $providers = collect();
        if (Schema::hasTable('providers')) {
            $pq = DB::table('providers');
            if (Schema::hasColumn('providers', 'deleted_at')) {
                $pq->where(function ($q) {
                    $q->where('deleted_at', '!=', 1)->orWhereNull('deleted_at');
                });
            }
            $orderCol = Schema::hasColumn('providers', 'sort_order') ? 'sort_order' : 'provider_name';
            $providers = $pq->orderBy($orderCol)->orderBy('provider_name')->get();
        }

        $grouped = $services->map(function ($service, $index) use ($providers, $services) {
            $ops = $providers->where('service_id', (int) $service->id)->values();
            $service->icon_url = $this->serviceIconUrl($service->service_icon ?? '');
            $service->operators = $ops;
            $service->position = $index + 1;
            $service->is_first = $index === 0;
            $service->is_last = $index === ($services->count() - 1);

            return $service;
        });

        return view('admin.system._services-board', [
            'services' => $grouped,
        ]);
    }

    public function moveItem(Request $post)
    {
        $this->ensureServiceColumns();
        $type = (string) $post->input('type', 'service');
        $id = (int) $post->id;
        $direction = (string) $post->input('direction', 'up');
        if ($id < 1 || ! in_array($direction, ['up', 'down'], true)) {
            return response()->json(['type' => 'error', 'message' => 'Invalid request.']);
        }

        if ($type === 'operator') {
            return $this->swapSort('providers', $id, $direction, 'service_id');
        }

        return $this->swapSort('services', $id, $direction);
    }

    public function getData(Request $post)
    {
        $this->ensureServiceColumns();
        $get = DB::table('services')->where('id', $post->id)->first();
        if ($get) {
            $get->icon_url = $this->serviceIconUrl($get->service_icon ?? '');

            return response()->json([
                'type' => 'success',
                'message' => 'Get sucessfuly',
                'data' => $get,
            ]);
        }

        return response()->json([
            'type' => 'error',
            'message' => 'Something went wrong!',
        ]);
    }

    public function updateData(Request $post)
    {
        $this->ensureServiceColumns();

        $rules = [
            'edit_id' => 'nullable|numeric|min:0',
            'service_name' => 'required|string|max:100',
            'status' => 'required|numeric|digits:1',
            'service_down' => 'nullable|in:0,1',
            'catalog_group' => 'nullable|in:recharge,bbps',
            'service_icon' => 'nullable|mimes:jpeg,jpg,png,webp,gif|max:4096',
        ];
        $validator = \Validator::make($post->all(), $rules);
        if ($validator->fails()) {
            $error = '';
            foreach ($validator->errors()->messages() as $value) {
                $error = $value[0];
            }

            return response()->json(['type' => 'error', 'message' => $error]);
        }

        $id = (int) ($post->edit_id ?: 0);
        $service = $id > 0 ? DB::table('services')->where('id', $id)->first() : null;
        if ($id > 0 && ! $service) {
            return response()->json(['type' => 'error', 'message' => 'Service not found.']);
        }

        $iconPath = $service->service_icon ?? null;
        if ($post->hasFile('service_icon')) {
            $file = $post->file('service_icon');
            $fileName = 'svc_'.time().'_'.bin2hex(random_bytes(4)).'.'.$file->getClientOriginalExtension();
            $bytes = file_get_contents($file->getRealPath());
            foreach ($this->serviceIconDirectories() as $dir) {
                if (! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($dir.DIRECTORY_SEPARATOR.$fileName, $bytes);
            }
            $iconPath = 'service_icon/'.$fileName;
        }

        $name = trim((string) $post->service_name);
        $payload = [
            'service_name' => $name,
            'status' => (int) $post->status,
            'updated_at' => Carbon::now(),
        ];
        if (Schema::hasColumn('services', 'service_down')) {
            $payload['service_down'] = (int) ($post->input('service_down', $service->service_down ?? 0));
        }
        if (Schema::hasColumn('services', 'service_icon')) {
            $payload['service_icon'] = $iconPath;
        }
        if (Schema::hasColumn('services', 'catalog_group')) {
            $payload['catalog_group'] = in_array((string) $post->catalog_group, ['recharge', 'bbps'], true)
                ? (string) $post->catalog_group
                : 'bbps';
        }

        if ($service) {
            DB::table('services')->where('id', $service->id)->update($payload);
            if (Schema::hasColumn('providers', 'operator_type')) {
                DB::table('providers')->where('service_id', $service->id)->update([
                    'operator_type' => $name,
                ]);
            }

            return response()->json([
                'type' => 'success',
                'message' => 'Service updated.',
            ]);
        }

        if (Schema::hasColumn('services', 'sort_order')) {
            $payload['sort_order'] = ((int) DB::table('services')->max('sort_order')) + 1;
        }
        if (Schema::hasColumn('services', 'deleted_at')) {
            $payload['deleted_at'] = 0;
        }
        $payload['created_at'] = Carbon::now();
        DB::table('services')->insert($payload);

        return response()->json([
            'type' => 'success',
            'message' => 'Service added.',
        ]);
    }

    public function deleteData(Request $post)
    {
        $this->ensureServiceColumns();
        $id = (int) $post->id;
        if ($id < 1) {
            return response()->json(['type' => 'error', 'message' => 'Invalid service.']);
        }

        $service = DB::table('services')->where('id', $id)->first();
        if (! $service) {
            return response()->json(['type' => 'error', 'message' => 'Service not found.']);
        }

        $operatorCount = 0;
        if (Schema::hasTable('providers')) {
            $q = DB::table('providers')->where('service_id', $id);
            if (Schema::hasColumn('providers', 'deleted_at')) {
                $q->where(function ($w) {
                    $w->where('deleted_at', '!=', 1)->orWhereNull('deleted_at');
                });
            }
            $operatorCount = $q->count();
        }

        if ($operatorCount > 0 && ! $post->boolean('confirm_with_operators')) {
            return response()->json([
                'type' => 'confirm',
                'message' => 'This service has '.$operatorCount.' operator(s). Delete service anyway?',
            ]);
        }

        if (Schema::hasColumn('services', 'deleted_at')) {
            DB::table('services')->where('id', $id)->update([
                'deleted_at' => 1,
                'status' => 0,
                'updated_at' => Carbon::now(),
            ]);
        } else {
            DB::table('services')->where('id', $id)->delete();
        }

        return response()->json([
            'type' => 'success',
            'message' => 'Service deleted.',
        ]);
    }

    public function updateStatus(Request $post)
    {
        $this->ensureServiceDownColumn();
        $id = (int) $post->id;
        $status = (int) $post->status;
        if ($id < 1 || ! in_array($status, [0, 1], true)) {
            return response()->json(['type' => 'error', 'message' => 'Invalid request.']);
        }
        $ok = DB::table('services')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => Carbon::now(),
        ]);
        if (! $ok) {
            return response()->json(['type' => 'error', 'message' => 'Service not found.']);
        }

        return response()->json([
            'type' => 'success',
            'message' => $status === 1 ? 'Service turned ON for user web and app.' : 'Service turned OFF for user web and app.',
        ]);
    }

    public function updateDown(Request $post)
    {
        $this->ensureServiceDownColumn();
        $id = (int) $post->id;
        $down = (int) $post->service_down;
        if ($id < 1 || ! in_array($down, [0, 1], true)) {
            return response()->json(['type' => 'error', 'message' => 'Invalid request.']);
        }
        $ok = DB::table('services')->where('id', $id)->update([
            'service_down' => $down,
            'updated_at' => Carbon::now(),
        ]);
        if (! $ok) {
            return response()->json(['type' => 'error', 'message' => 'Service not found.']);
        }

        return response()->json([
            'type' => 'success',
            'message' => $down === 1 ? 'Service marked DOWN. App and web will show DOWN.' : 'Service is UP again.',
        ]);
    }

    private function swapSort(string $table, int $id, string $direction, ?string $groupColumn = null)
    {
        $row = DB::table($table)->where('id', $id)->first();
        if (! $row) {
            return response()->json(['type' => 'error', 'message' => 'Record not found.']);
        }

        $query = DB::table($table);
        if ($groupColumn && isset($row->{$groupColumn})) {
            $query->where($groupColumn, $row->{$groupColumn});
        }
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->where(function ($q) {
                $q->where('deleted_at', '!=', 1)->orWhereNull('deleted_at');
            });
        }

        $rows = $query->orderBy('sort_order')->orderBy('id')->get(['id', 'sort_order']);
        $ids = $rows->pluck('id')->map(fn ($v) => (int) $v)->values();
        $index = $ids->search($id);
        if ($index === false) {
            return response()->json(['type' => 'error', 'message' => 'Record not found.']);
        }
        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        if ($swap < 0 || $swap >= $ids->count()) {
            return response()->json(['type' => 'error', 'message' => 'Already at the edge.']);
        }

        $otherId = (int) $ids[$swap];
        $currentOrder = (int) ($rows[$index]->sort_order ?: ($index + 1));
        $otherOrder = (int) ($rows[$swap]->sort_order ?: ($swap + 1));
        if ($currentOrder === $otherOrder) {
            $currentOrder = $index + 1;
            $otherOrder = $swap + 1;
        }

        DB::table($table)->where('id', $id)->update(['sort_order' => $otherOrder]);
        DB::table($table)->where('id', $otherId)->update(['sort_order' => $currentOrder]);

        return response()->json(['type' => 'success', 'message' => 'Order updated.']);
    }

    private function ensureServiceColumns(): void
    {
        $this->ensureServiceIconColumn();
        $this->ensureServiceDownColumn();
        $this->ensureCatalogGroupColumn();
        try {
            if (Schema::hasTable('services') && ! Schema::hasColumn('services', 'sort_order')) {
                Schema::table('services', function ($table) {
                    $table->unsignedInteger('sort_order')->default(0);
                });
            }
            if (Schema::hasTable('services') && Schema::hasColumn('services', 'sort_order')) {
                DB::table('services')->where('sort_order', 0)->update(['sort_order' => DB::raw('id')]);
            }
            if (Schema::hasTable('providers') && ! Schema::hasColumn('providers', 'sort_order')) {
                Schema::table('providers', function ($table) {
                    $table->unsignedInteger('sort_order')->default(0);
                });
            }
            if (Schema::hasTable('providers') && Schema::hasColumn('providers', 'sort_order')) {
                DB::table('providers')->where('sort_order', 0)->update(['sort_order' => DB::raw('id')]);
            }
        } catch (\Throwable $e) {
        }
    }

    private function ensureCatalogGroupColumn(): void
    {
        try {
            if (! Schema::hasTable('services')) {
                return;
            }
            if (! Schema::hasColumn('services', 'catalog_group')) {
                Schema::table('services', function ($table) {
                    $table->string('catalog_group', 20)->nullable();
                });
            }
            DB::table('services')->whereIn('id', [1, 2, 4])->where(function ($q) {
                $q->whereNull('catalog_group')->orWhere('catalog_group', '');
            })->update(['catalog_group' => 'recharge']);
            DB::table('services')->where(function ($q) {
                $q->whereNull('catalog_group')->orWhere('catalog_group', '');
            })->update(['catalog_group' => 'bbps']);
        } catch (\Throwable $e) {
        }
    }

    private function ensureServiceDownColumn(): void
    {
        try {
            if (Schema::hasTable('services') && ! Schema::hasColumn('services', 'service_down')) {
                Schema::table('services', function ($table) {
                    $table->unsignedTinyInteger('service_down')->default(0);
                });
            }
        } catch (\Throwable $e) {
        }
    }

    private function ensureServiceIconColumn(): void
    {
        try {
            if (! Schema::hasTable('services')) {
                return;
            }
            if (! Schema::hasColumn('services', 'service_icon')) {
                Schema::table('services', function ($table) {
                    $table->string('service_icon', 255)->nullable();
                });
            }
        } catch (\Throwable $e) {
        }
    }

    private function serviceIconDirectories(): array
    {
        $dirs = [public_path('service_icon')];
        $userDir = dirname(base_path()).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'service_icon';
        if ($userDir !== $dirs[0]) {
            $dirs[] = $userDir;
        }

        return array_unique($dirs);
    }

    private function serviceIconUrl(?string $icon): string
    {
        $icon = trim((string) $icon);
        if ($icon === '') {
            return asset('assets/images/users/user-dummy-img.jpg');
        }
        $path = str_contains($icon, '/') ? $icon : 'service_icon/'.$icon;

        return function_exists('admin_asset') ? admin_asset($path) : asset($path);
    }
}
