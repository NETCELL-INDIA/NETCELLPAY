<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ServiceController extends Controller
{
    public function index(Request $post)
    {
        return view('admin.system.services');
    }

    public function fetchAll(Request $post)
    {
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
        $total_row = DB::table('services')->get();
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

        $list = DB::table('services')->orderBy('id', 'DESC')->offset($start)->limit($limit)->get();
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
                <th>Name</th>
                <th>Status</th>
                <th>Created at</th>
                <th>Updated at</th>
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
                $output .= '<tr>
                <td>' . $i . '</td>
                <td>' . $list->service_name . '</td>
                <td>' . $status . '</td>
                <td>' . $list->created_at . '</td>
                <td>' . $list->updated_at . '</td>
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
}
