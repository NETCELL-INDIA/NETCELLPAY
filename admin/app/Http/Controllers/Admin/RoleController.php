<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class RoleController extends Controller
{
    public function index(Request $post)
    {
        return view('admin.system.role');
    }

    public function fetchAll(Request $post)
    {
        $list = DB::table('roles')->where('slug', '!=' , "superadmin")->orderBy('id', 'DESC')->get();
        $output = '';
        if ($list->count() > 0) {
            $output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Created at</th>
                <th>Updated at</th>
              </tr>
            </thead>
            <tbody>';
            $i = 1;
            foreach ($list as $list) {
                if ($list->status == "1") {
                    $status = '<span class="badge rounded-pill text-bg-success">Active</span>';
                } else {
                    $status = '<span class="badge rounded-pill text-bg-danger">Deactive</span>';
                }
                $output .= '<tr>
                <td>' . $i . '</td>
                <td>' . $list->role_name . '</td>
                <td>' . $list->slug . '</td>
                <td>' . $status . '</td>
                <td>' . $list->created_at . '</td>
                <td>' . $list->updated_at . '</td>
              </tr>';
                $i++;
            }
            $output .= '</tbody></table>';
            echo $output;
        } else {
            echo '<h4 class="text-center text-secondary my-3">No record found</h4>';
        }
    }

}
