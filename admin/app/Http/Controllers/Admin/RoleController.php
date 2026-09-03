<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminMenuService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(Request $post)
    {
        AdminMenuService::ensureTables();

        return view('admin.system.role');
    }

    public function fetchAll(Request $post)
    {
        AdminMenuService::ensureTables();

        $query = DB::table('roles');
        if (Schema::hasColumn('roles', 'deleted_at')) {
            $query->where('deleted_at', '!=', 1);
        }
        if (Schema::hasColumn('roles', 'slug')) {
            $query->where(function ($q) {
                $q->where('slug', '!=', 'superadmin')->orWhereNull('slug');
            });
        }
        $query->where('id', '!=', AdminMenuService::SUPERADMIN_ROLE_ID);
        $list = $query->orderBy('id', 'ASC')->get();
        $output = '';
        if ($list->count() > 0) {
            $output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Admin Login</th>
                <th>Status</th>
                <th>Created at</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>';
            $i = 1;
            foreach ($list as $row) {
                if ((string) ($row->status ?? '') === '1') {
                    $status = '<span class="badge rounded-pill text-bg-success">Active</span>';
                } else {
                    $status = '<span class="badge rounded-pill text-bg-danger">Deactive</span>';
                }
                $isAdmin = (int) ($row->is_admin ?? 0) === 1;
                $isSystem = in_array((int) $row->id, AdminMenuService::SYSTEM_ROLE_IDS, true);
                $output .= '<tr>
                <td>' . $i . '</td>
                <td>' . e($row->role_name ?? '-') . '</td>
                <td>' . e($row->slug ?? '-') . '</td>
                <td>' . ($isAdmin ? '<span class="badge rounded-pill text-bg-info">Yes</span>' : '<span class="badge rounded-pill text-bg-secondary">No</span>') . '</td>
                <td>' . $status . '</td>
                <td>' . e($row->created_at ?? '-') . '</td>
                <td>
                    <a href="javascript:void(0)" class="badge text-bg-secondary editRole" data-id="' . $row->id . '"><i class="ri-pencil-fill align-bottom"></i> Edit</a>
                    '.($isAdmin ? '<a href="javascript:void(0)" class="badge text-bg-primary setPermission" data-id="' . $row->id . '"><i class="ri-lock-2-line align-bottom"></i> Menu Permission</a>' : '').'
                    '.($isSystem ? '' : '<a href="javascript:void(0)" class="badge text-bg-danger deleteRole" data-id="' . $row->id . '"><i class="ri-delete-bin-fill align-bottom"></i> Delete</a>').'
                </td>
              </tr>';
                $i++;
            }
            $output .= '</tbody></table>';
            echo $output;
        } else {
            echo '<h4 class="text-center text-secondary my-3">No record found</h4>';
        }
    }

    public function getData(Request $post)
    {
        $row = DB::table('roles')->where('id', $post->id)->first();
        if (! $row || (int) $row->id === AdminMenuService::SUPERADMIN_ROLE_ID) {
            return response()->json(['type' => 'error', 'message' => 'Role not found.']);
        }

        return response()->json(['type' => 'success', 'data' => $row]);
    }

    public function updateData(Request $post)
    {
        AdminMenuService::ensureTables();
        $rules = [
            'role_name' => 'required|string|max:80',
            'status' => 'required|numeric|digits:1',
        ];
        $validator = \Validator::make($post->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['type' => 'error', 'message' => $validator->errors()->first()]);
        }

        $name = trim((string) $post->role_name);
        $slug = Str::slug($name) ?: ('role-'.time());
        $editId = (int) $post->edit_id;
        $isAdmin = $post->boolean('is_admin') ? 1 : 0;

        if ($editId > 0) {
            if ($editId === AdminMenuService::SUPERADMIN_ROLE_ID) {
                return response()->json(['type' => 'error', 'message' => 'Superadmin cannot be edited.']);
            }
            if (in_array($editId, [3, 4, 5, 6], true)) {
                $isAdmin = 0;
            }
            $dup = DB::table('roles')->where('id', '!=', $editId)->where(function ($q) use ($name, $slug) {
                $q->where('role_name', $name);
                if (Schema::hasColumn('roles', 'slug')) {
                    $q->orWhere('slug', $slug);
                }
            })->exists();
            if ($dup) {
                return response()->json(['type' => 'error', 'message' => 'Role name already exists.']);
            }
            $payload = [
                'role_name' => $name,
                'status' => (int) $post->status,
                'updated_at' => Carbon::now(),
            ];
            if (Schema::hasColumn('roles', 'slug')) {
                $payload['slug'] = $slug;
            }
            if (Schema::hasColumn('roles', 'is_admin')) {
                $payload['is_admin'] = $isAdmin;
            }
            DB::table('roles')->where('id', $editId)->update($payload);

            return response()->json(['type' => 'success', 'message' => 'Role updated.', 'id' => $editId]);
        }

        $dup = DB::table('roles')->where('role_name', $name)->exists();
        if ($dup) {
            return response()->json(['type' => 'error', 'message' => 'Role name already exists.']);
        }
        $row = [
            'role_name' => $name,
            'status' => (int) $post->status,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        if (Schema::hasColumn('roles', 'slug')) {
            $row['slug'] = $slug;
        }
        if (Schema::hasColumn('roles', 'is_admin')) {
            $row['is_admin'] = $isAdmin;
        }
        if (Schema::hasColumn('roles', 'deleted_at')) {
            $row['deleted_at'] = 0;
        }
        $id = DB::table('roles')->insertGetId($row);
        if ($isAdmin === 1 && Schema::hasTable('role_menus')) {
            DB::table('role_menus')->insert([
                'role_id' => $id,
                'menu_key' => 'dashboard',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        return response()->json(['type' => 'success', 'message' => 'Role created.', 'id' => $id, 'open_permission' => $isAdmin === 1]);
    }

    public function deleteData(Request $post)
    {
        $id = (int) $post->id;
        if ($id < 1 || in_array($id, AdminMenuService::SYSTEM_ROLE_IDS, true)) {
            return response()->json(['type' => 'error', 'message' => 'System roles cannot be deleted.']);
        }
        $users = DB::table('users')->where('role_id', $id)->count();
        if ($users > 0) {
            return response()->json(['type' => 'error', 'message' => 'This role is assigned to '.$users.' user(s). Move them first.']);
        }
        if (Schema::hasColumn('roles', 'deleted_at')) {
            DB::table('roles')->where('id', $id)->update(['deleted_at' => 1, 'updated_at' => Carbon::now()]);
        } else {
            DB::table('roles')->where('id', $id)->delete();
        }
        if (Schema::hasTable('role_menus')) {
            DB::table('role_menus')->where('role_id', $id)->delete();
        }

        return response()->json(['type' => 'success', 'message' => 'Role deleted.']);
    }

    public function getPermissions(Request $post)
    {
        AdminMenuService::ensureTables();
        $id = (int) $post->id;
        $row = DB::table('roles')->where('id', $id)->first();
        if (! $row || (int) ($row->is_admin ?? 0) !== 1) {
            return response()->json(['type' => 'error', 'message' => 'Menu permission is only for Admin-login roles.']);
        }
        $keys = AdminMenuService::allowedKeys($id);

        return response()->json([
            'type' => 'success',
            'role_name' => $row->role_name,
            'keys' => $keys,
            'catalog' => AdminMenuService::catalog(),
        ]);
    }

    public function savePermissions(Request $post)
    {
        AdminMenuService::ensureTables();
        $id = (int) $post->id;
        $row = DB::table('roles')->where('id', $id)->first();
        if (! $row || (int) ($row->is_admin ?? 0) !== 1) {
            return response()->json(['type' => 'error', 'message' => 'Menu permission is only for Admin-login roles.']);
        }
        $keys = $post->keys;
        if (! is_array($keys)) {
            $keys = [];
        }
        $keys = array_values(array_unique(array_filter(array_map('strval', $keys))));
        if (! in_array('dashboard', $keys, true)) {
            $keys[] = 'dashboard';
        }
        DB::table('role_menus')->where('role_id', $id)->delete();
        $now = Carbon::now();
        foreach ($keys as $key) {
            if (strlen($key) > 120) {
                continue;
            }
            DB::table('role_menus')->insert([
                'role_id' => $id,
                'menu_key' => $key,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return response()->json(['type' => 'success', 'message' => 'Menu permission saved.']);
    }
}
