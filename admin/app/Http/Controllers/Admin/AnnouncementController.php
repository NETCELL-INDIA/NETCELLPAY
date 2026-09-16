<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    public function index()
    {
        $this->ensureSchema();

        $roles = collect();
        if (Schema::hasTable('roles')) {
            $q = DB::table('roles')->orderBy('id');
            if (Schema::hasColumn('roles', 'deleted_at')) {
                $q->where(function ($w) {
                    $w->whereNull('deleted_at')->orWhere('deleted_at', 0);
                });
            }
            $roles = $q->where('id', '>', 1)->get(['id', 'role_name']);

            // Preferred display order for Add News checkboxes
            $order = [
                'api partner' => 1,
                'api user' => 1,
                'api' => 1,
                'master distributor' => 2,
                'master white label' => 2,
                'distributor' => 3,
                'retailer' => 4,
                'sub admin' => 5,
            ];
            $roles = $roles->sortBy(function ($role) use ($order) {
                $name = strtolower(trim((string) $role->role_name));

                return $order[$name] ?? (100 + (int) $role->id);
            })->values();
        }

        return view('admin.system.announcement', compact('roles'));
    }

    public function list(Request $request)
    {
        $this->ensureSchema();

        $rows = DB::table('announcements')
            ->orderByDesc('id')
            ->get();

        $html = '';
        if ($rows->isEmpty()) {
            $html = '<tr><td colspan="7" class="text-center text-muted py-4">No news found</td></tr>';
        } else {
            $i = 1;
            foreach ($rows as $row) {
                $checked = (int) ($row->status ?? 0) === 1 ? 'checked' : '';
                $createDate = $row->created_at
                    ? Carbon::parse($row->created_at)->format('d-m-Y')
                    : '-';
                $expiryDate = ! empty($row->expiry_date)
                    ? Carbon::parse($row->expiry_date)->format('d-m-Y')
                    : '-';
                $title = e($row->title ?: '-');
                $news = e(Str::limit(strip_tags((string) ($row->message ?: '')), 500));
                $html .= '<tr>
                    <td>'.$i.'</td>
                    <td>'.$title.'</td>
                    <td class="news-cell">'.$news.'</td>
                    <td>'.$createDate.'</td>
                    <td>'.$expiryDate.'</td>
                    <td>
                        <div class="form-check form-switch form-switch-md mb-0">
                            <input class="form-check-input announcement-status" type="checkbox" role="switch"
                                data-id="'.$row->id.'" '.$checked.'>
                        </div>
                    </td>
                    <td class="text-nowrap">
                        <button type="button" class="btn btn-sm btn-soft-primary btn-edit-news me-1" data-id="'.$row->id.'" title="Edit">
                            <i class="ri-pencil-line"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-soft-danger btn-delete-news" data-id="'.$row->id.'" title="Delete">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </td>
                </tr>';
                $i++;
            }
        }

        return response()->json([
            'type' => 'success',
            'rows' => $html,
            'total' => $rows->count(),
        ]);
    }

    public function getData(Request $request)
    {
        $this->ensureSchema();

        $id = (int) $request->id;
        $row = DB::table('announcements')->where('id', $id)->first();
        if (! $row) {
            return response()->json(['type' => 'error', 'message' => 'News not found']);
        }

        $targetRoles = [];
        if (! empty($row->target_roles)) {
            $decoded = json_decode((string) $row->target_roles, true);
            if (is_array($decoded)) {
                $targetRoles = array_values(array_map('intval', $decoded));
            }
        }

        return response()->json([
            'type' => 'success',
            'message' => 'Fetched successfully',
            'data' => [
                'id' => $row->id,
                'title' => $row->title ?? '',
                'message' => $row->message ?? '',
                'expiry_date' => $row->expiry_date
                    ? Carbon::parse($row->expiry_date)->format('Y-m-d')
                    : '',
                'status' => (int) ($row->status ?? 0),
                'target_roles' => $targetRoles,
            ],
        ]);
    }

    public function save(Request $request)
    {
        $this->ensureSchema();

        $validator = Validator::make($request->all(), [
            'id' => 'nullable|integer',
            'title' => 'required|string|max:191',
            'message' => 'required|string',
            'expiry_date' => 'nullable|date',
            'status' => 'nullable|in:0,1',
        ]);

        if ($validator->fails()) {
            $error = '';
            foreach ($validator->errors()->messages() as $messages) {
                $error = $messages[0];
                break;
            }

            return response()->json(['type' => 'error', 'message' => $error]);
        }

        try {
            $id = (int) ($request->id ?: 0);
            $status = 1;
            $targetRoles = $request->input('target_roles', []);
            if (! is_array($targetRoles)) {
                $targetRoles = [];
            }
            $targetRoles = array_values(array_unique(array_filter(array_map('intval', $targetRoles))));

            $payload = [
                'title' => trim((string) $request->title),
                'message' => trim((string) $request->message),
                'expiry_date' => $request->expiry_date ?: null,
                'status' => $status,
                'type' => 'info',
                'updated_at' => Carbon::now(),
            ];

            if (Schema::hasColumn('announcements', 'target_roles')) {
                $payload['target_roles'] = $targetRoles !== [] ? json_encode($targetRoles) : null;
            }

            if ($id > 0) {
                $exists = DB::table('announcements')->where('id', $id)->exists();
                if (! $exists) {
                    return response()->json(['type' => 'error', 'message' => 'News not found']);
                }
                DB::table('announcements')->where('id', $id)->update($payload);

                return response()->json(['type' => 'success', 'message' => 'News updated successfully']);
            }

            $payload['created_at'] = Carbon::now();
            try {
                DB::table('announcements')->insert($payload);
            } catch (\Throwable $insertErr) {
                // Legacy tables may lack AUTO_INCREMENT on id
                if (stripos($insertErr->getMessage(), "doesn't have a default value") !== false
                    || stripos($insertErr->getMessage(), 'Field \'id\'') !== false) {
                    $this->fixIdAutoIncrement();
                    $nextId = ((int) DB::table('announcements')->max('id')) + 1;
                    $payload['id'] = max(1, $nextId);
                    DB::table('announcements')->insert($payload);
                } else {
                    throw $insertErr;
                }
            }

            return response()->json(['type' => 'success', 'message' => 'News added successfully']);
        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Save failed: '.$e->getMessage(),
            ], 422);
        }
    }

    public function toggleStatus(Request $request)
    {
        $this->ensureSchema();

        $id = (int) $request->id;
        $row = DB::table('announcements')->where('id', $id)->first();
        if (! $row) {
            return response()->json(['type' => 'error', 'message' => 'News not found']);
        }

        $status = (int) $request->status === 1 ? 1 : 0;
        DB::table('announcements')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => Carbon::now(),
        ]);

        return response()->json([
            'type' => 'success',
            'message' => $status ? 'News activated' : 'News deactivated',
        ]);
    }

    public function deleteData(Request $request)
    {
        $this->ensureSchema();

        $id = (int) $request->id;
        if ($id <= 0) {
            return response()->json(['type' => 'error', 'message' => 'Invalid id']);
        }

        DB::table('announcements')->where('id', $id)->delete();

        return response()->json(['type' => 'success', 'message' => 'News deleted successfully']);
    }

    /** @deprecated kept for old routes */
    public function updateData(Request $request)
    {
        return $this->save($request);
    }

    private function ensureSchema(): void
    {
        if (! Schema::hasTable('announcements')) {
            Schema::create('announcements', function ($table) {
                $table->id();
                $table->string('title', 191)->nullable();
                $table->string('type', 50)->nullable()->default('info');
                $table->longText('message')->nullable();
                $table->date('expiry_date')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->text('target_roles')->nullable();
                $table->timestamps();
            });

            return;
        }

        if (! Schema::hasColumn('announcements', 'title')) {
            Schema::table('announcements', function ($table) {
                $table->string('title', 191)->nullable()->after('id');
            });
        }
        if (! Schema::hasColumn('announcements', 'expiry_date')) {
            Schema::table('announcements', function ($table) {
                $table->date('expiry_date')->nullable()->after('message');
            });
        }
        if (! Schema::hasColumn('announcements', 'status')) {
            Schema::table('announcements', function ($table) {
                $table->tinyInteger('status')->default(1)->after('expiry_date');
            });
            DB::table('announcements')->whereNull('status')->update(['status' => 1]);
        }
        if (! Schema::hasColumn('announcements', 'type')) {
            Schema::table('announcements', function ($table) {
                $table->string('type', 50)->nullable()->default('info')->after('title');
            });
        }
        if (! Schema::hasColumn('announcements', 'target_roles')) {
            Schema::table('announcements', function ($table) {
                $table->text('target_roles')->nullable()->after('status');
            });
        }

        $this->fixIdAutoIncrement();
    }

    private function fixIdAutoIncrement(): void
    {
        try {
            $col = collect(DB::select('SHOW COLUMNS FROM announcements WHERE Field = ?', ['id']))->first();
            if (! $col) {
                return;
            }
            $extra = strtolower((string) ($col->Extra ?? ''));
            if (str_contains($extra, 'auto_increment')) {
                return;
            }

            $indexes = collect(DB::select('SHOW INDEX FROM announcements'));
            $hasPrimary = $indexes->contains(function ($r) {
                return ($r->Key_name ?? '') === 'PRIMARY';
            });
            if (! $hasPrimary) {
                DB::statement('ALTER TABLE announcements ADD PRIMARY KEY (id)');
            }

            $max = (int) DB::table('announcements')->max('id');
            DB::statement('ALTER TABLE announcements MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
            if ($max > 0) {
                DB::statement('ALTER TABLE announcements AUTO_INCREMENT='.($max + 1));
            }
        } catch (\Throwable $e) {
            // Ignore — save() has a manual-id fallback.
        }
    }
}
