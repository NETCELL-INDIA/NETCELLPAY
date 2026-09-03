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
        $this->ensureServiceIconColumn();

        return view('admin.system.services');
    }

    public function fetchAll(Request $post)
    {
        $this->ensureServiceIconColumn();

        $rules = array(
            'page' => 'required|numeric',
            'limit' => 'required|numeric',
        );

        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return '<h4 class="text-center text-secondary my-3">'.$error.'</h4>';
        }

        if($post->page){
            $page = $post->page;
        }else{
            $page = 1;
        }

        if($post->limit){
            if($post->limit <= 50){
                $limit = $post->limit;
            }else{
                $limit = 10;
            }
        }else{
            $limit = 10;
        }
        $start= ($page-1) * $limit;
        $query = DB::table('services');
        if (Schema::hasColumn('services', 'deleted_at')) {
            $query->where(function ($q) {
                $q->where('deleted_at', '!=', 1)->orWhereNull('deleted_at');
            });
        }
        $total_row = (clone $query)->get();
        $total_row_count = $total_row->count();
        $total_pages = ceil($total_row_count / $limit);
        $page_link = '';
        for ($i1=1; $i1<=$total_pages; $i1++) {
            if($page == $i1){
                $act = "active";
                $d = "";
            }else{
                $act = "";
                $d = 'onclick="tableSearch('.$i1.')"';
            }
            $page_link .= '<li class="page-item "><a href="javascript:void(0)" class="page-link '.$act.'" '.$d.'>'.$i1.'</a></li>';
        };

        $list = (clone $query)->orderBy('id', 'ASC')->offset($start)->limit($limit)->get();
        $list_count = $list->count();
        $output = '';
        if ($list->count() > 0) {
            $output .= '<div class="table-responsive">';
            $output .= '<div class="row">
                    <div class="col-sm-1">
                        <select class="form-select mb-3 page_limit" aria-label="Page Limit" id="page_limit">
                            <option ' . ($limit == "5" ? "selected" : "") . ' value="5">5</option>
                            <option ' . ($limit == "10" ? "selected" : "") . ' value="10" >10</option>
                            <option ' . ($limit == "15" ? "selected" : "") . ' value="15">15</option>
                            <option ' . ($limit == "25" ? "selected" : "") . ' value="25">25</option>
                            <option ' . ($limit == "50" ? "selected" : "") . ' value="50">50</option>
                        </select>
                    </div>
                    <div class="col-sm-9">
                        </br>
                    </div>

                    <div class="col-sm-2">
                        <input type="text" class="form-control" id="searchValueTable" placeholder="Enter Search Value">
                    </div>
                </div><br>';
            $output .= '<table class="table table-bordered table-nowrap" id="pagination_table"><thead>
              <tr>
                <th>ID</th>
                <th>Icon</th>
                <th>Name</th>
                <th>Status</th>
                <th>Created at</th>
                <th>Updated at</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>';
            $i=$start + 1;
            foreach ($list as $list) {
                if ($list->status == "1") {
                    $status = '<span class="badge rounded-pill text-bg-success">Active</span>';
                } else {
                    $status = '<span class="badge rounded-pill text-bg-danger">Deactive</span>';
                }
                $iconSrc = $this->serviceIconUrl($list->service_icon ?? '');
                $output .= '<tr>
                <td>' . $i . '</td>
                <td><img src="'.e($iconSrc).'" alt="" class="rounded-3" style="width:40px;height:40px;object-fit:cover;border:1px solid #e2e8f0;"></td>
                <td>' . e($list->service_name) . '</td>
                <td>' . $status . '</td>
                <td>' . $list->created_at . '</td>
                <td>' . $list->updated_at . '</td>
                <td>
                    <a href="javascript:void(0)" id="' . $list->id . '" class="badge text-bg-secondary editDetails"><i class="ri-pencil-fill align-bottom"></i> Edit Name / Icon</a>
                </td>
              </tr>';
                $i++;
            }
            $output .= '</tbody></table>';
            $output .= '<div class="row">
                <div class="col-sm-2">
                        <span>Showing '.($start + 1).' to '.($start + $list_count).' of '.$list_count.' entries ('.$total_row_count.' entries)</span>
                </div>
                <div class="col-sm-6">
                   <br>
                </div>
                <div class="col-sm-4">
                        <nav aria-label="Page navigation example">
                        <ul class="pagination">
                            <li class="page-item '.($page  == 1 || $page  == 0 ? "disabled" : "").'">
                                <a class="page-link" href="javascript:void(0)" onclick="tableSearch('.($page - 1).')">← &nbsp; Prev</a>
                            </li>
                                '.$page_link.'
                            <li class="page-item '.($page + 1 == $i1 ? "disabled" : "").'">
                                <a class="page-link" href="javascript:void(0)" onclick="tableSearch('.($page + 1 == $i1 ? $page : $page + 1).')">Next &nbsp; →</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div><br>';

            $output .= '</div>';
            echo $output;
        } else {
            echo '<h4 class="text-center text-secondary my-3">No record found</h4>';
        }
    }

    public function getData(Request $post)
    {
        $this->ensureServiceIconColumn();
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
        $this->ensureServiceIconColumn();

        $rules = [
            'edit_id' => 'required|numeric|min:1',
            'service_name' => 'required|string|max:100',
            'status' => 'required|numeric|digits:1',
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

        $service = DB::table('services')->where('id', $post->edit_id)->first();
        if (! $service) {
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
        DB::table('services')->where('id', $service->id)->update([
            'service_name' => $name,
            'status' => (int) $post->status,
            'service_icon' => $iconPath,
            'updated_at' => Carbon::now(),
        ]);

        if (Schema::hasColumn('providers', 'operator_type')) {
            DB::table('providers')->where('service_id', $service->id)->update([
                'operator_type' => $name,
            ]);
        }

        return response()->json([
            'type' => 'success',
            'message' => 'Service name and icon updated.',
        ]);
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
