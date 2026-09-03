<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Session;

class SchemeController extends Controller
{
    public function index(Request $post)
    {
        //$data[]'service'] = DB::table('providers')->where('deleted_at', '!=' , 1)->orderBy('id', 'ASC')->get();
        return view('admin.system.scheme');
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
        $total_row = DB::table('schemes')->where('deleted_at', '!=', 1)->get();
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
        $list = DB::table('schemes')->where('deleted_at', '!=', 1)->orderBy('id', 'DESC')->offset($start)->limit($limit)->get();
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
                        <th scope="col">ID</th>
                        <th scope="col">Scheme Name</th>
                        <th scope="col">Status</th>
                        <th scope="col">Created at</th>
                        <th scope="col">Updated at</th>
                        <th scope="col">Action</th>
                    </tr>
                    </thead>
            <tbody>';
            $i=$start + 1;
            foreach ($list as $list) {
                if($list->status=="1"){
                    $status = '<span class="badge rounded-pill text-bg-success">Active</span>';
                }else{
                    $status = '<span class="badge rounded-pill text-bg-danger">Deactive</span>';
                }
            	$output .= '<tr>
                <td>' . $i . '</td>
                <td>' . $list->scheme_name . '</td>
                <td>' . $status . '</td>
                <td>' . $list->created_at . '</td>
                <td>' . $list->updated_at . '</td>
                <td>
                    <a href="javascript:void(0)" id="' . $list->id . '" class="badge text-bg-secondary editDetails"><i class="ri-pencil-fill align-bottom"></i> Edit</a>
                    <a href="javascript:void(0)" id="' . $list->id . '" class="badge text-bg-danger deleteData"><i class="ri-delete-bin-fill align-bottom"></i> Delete</a>
                    <a href="javascript:void(0)" id="' . $list->id . '" class="badge text-bg-primary setCommission"><i class="ri-percent-line align-bottom"></i> Set Commission</a>
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

    public function deleteData(Request $post)
    {
        $delete = DB::table('schemes')->where('id', $post->id)->update(['deleted_at' => 1]);
        if ($delete) {
            $data['type'] = 'success';
            $data['message'] = "Delete sucessfuly";
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }

        return $data;

    }

    public function getData(Request $post)
    {
        $get = DB::table('schemes')->where('id', $post->id)->first();
        if ($get) {
            $data['type'] = 'success';
            $data['message'] = "Get sucessfuly";
            $data['data'] = $get;
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        return $data;
    }

    public function getCommissionData(Request $post)
    {

        $rules = array(
            'id' => 'required|numeric',
            'service' => 'required|numeric',
        );

        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json(
                array(
                    'type' => 'error',
                    'message' => $error
                )
            );
        }



        // $insert = "INSERT INTO `users` (`id`, `parent_id`, `role_id`, `scheme_id`, `first_name`, `middle_name`, `last_name`, `outlet_name`, `date_of_birth`, `email_address`, `mobile_number`, `password`, `gender`, `flat_door_no`, `wallet_balance`, `road_street`, `area_locality`, `city`, `state`, `district`, `bank_account_number`, `ifsc_code`, `branch_name`, `bank_account_type`, `minium_balance`, `register_by`, `profile_pic`, `login_key`, `login_type`, `otp`, `status`, `kyc_id`, `kyc_status`, `callback_url`, `ip_address`, `pan_status`, `pan_req_id`, `created_at`, `updated_at`, `deleted_at`) VALUES (NULL, '1', '6', '33', 'SHIBA', 'NARAYAN', 'TRIPATHY', 'SHIBA TECH', '2001-03-30', 'shiba96tripathy@gmail.com', '1234512345', '$2y$10$5bJWPuApGTpnpoepvKT7/u.Fw6fttOu/NJdPgcQvv0iULSenjyIoG', 'Male', 'PLOT NO. 18', '1510.00', 'Hari Nagar', 'Karniphatak', 'Jutheorda', 'RAJASTHAN', 'JAIPUR', '40658870706', 'SBIN0009292', 'KUSANG, CHANDANBHATI, BALANGIR', 'Savings', '0.00', 'Admin', 'hlSZEvD3r2ewKZnvlGnMhS55KrRkdSZ4OSkwFnnE1677610496.png', NULL, 'PASSWORD', NULL, '1', '0', 'Pending', 'https://callbackurl.com', '1.1.1.1', '1', NULL, '2023-03-01 00:22:27', '2023-03-01 21:52:52', '0')";
        //     $x = 1;
        //     while($x <= 100000) {
        //         DB::select($insert);
        //         echo "<pre>";print_r($x);
        //       $x++;
        //     }

        //echo "<pre>";print_r($post->all());die;

        $this->ensureApiPartnerCommissionColumns();

        $providers = DB::table('providers')->where('deleted_at', '!=', 1)->where('service_id', $post->service)->where('status', 1)->get();
        foreach ($providers as $key => $provider) {
            $commission = DB::table('scheme_commissions')->where('provider_id', $provider->id)->where('scheme_id', $post->id)->first();
            if ($commission) {
                $provider->wt_amount_type = $commission->wt_amount_type;
                $provider->wt_amount_value = $commission->wt_amount_value;
                ///
                $provider->md_amount_type = $commission->md_amount_type;
                $provider->md_amount_value = $commission->md_amount_value;
                //
                $provider->dt_amount_type = $commission->dt_amount_type;
                $provider->dt_amount_value = $commission->dt_amount_value;
                //
                $provider->rt_amount_type = $commission->rt_amount_type;
                $provider->rt_amount_value = $commission->rt_amount_value;
                $provider->ap_amount_type = $commission->ap_amount_type ?? 0;
                $provider->ap_amount_value = $commission->ap_amount_value ?? 0;
            } else {
                $provider->wt_amount_type = 0;
                $provider->wt_amount_value = 0;
                //
                $provider->md_amount_type = 0;
                $provider->md_amount_value = 0;
                //
                $provider->dt_amount_type = 0;
                $provider->dt_amount_value = 0;
                //
                $provider->rt_amount_type = 0;
                $provider->rt_amount_value = 0;
                $provider->ap_amount_type = 0;
                $provider->ap_amount_value = 0;
            }
        }
        // $data['type'] = 'success';
        // $data['message'] =  "Fatch Successfuly.";
        // $data['data'] = $providers;
        // return $data;
        $output = '';
        if ($providers->count() > 0) {
            $output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>Provider Name</th>

                <th>MD Com Type</th>
                <th>MD Com Val</th>

                <th>DT Com Type</th>
                <th>DT Com Val</th>

                <th>RT Com Type</th>
                <th>RT Com Val</th>

                <th>AP Com Type</th>
                <th>AP Com Val</th>

                <th>Action</th>
              </tr>
            </thead>
            <tbody>';
            $i = 1;
            foreach ($providers as $list) {
                $output .= '<tr id="' . $list->id . '_row">
                <td>' . $i . '</td>
                <td>
                <input type="hidden" id="' . $list->id . '_provider_id" name="provider_id[]" value="' . $list->id . '">
                ' . $list->provider_name . '
                </td>
                <td>
                    <select class="form-select" aria-label="Default select example" id="' . $list->id . '_md_comtype" name="md_comtype[]">
                        <option ' . ($list->md_amount_type == "Commission Flat" ? "selected" : "") . ' value="Commission Flat">Commission Flat</option>
                        <option ' . ($list->md_amount_type == "Commission Percent" ? "selected" : "") . ' value="Commission Percent">Commission Percent</option>
                        <option ' . ($list->md_amount_type == "Charge Flat" ? "selected" : "") . ' value="Charge Flat">Charge Flat</option>
                        <option ' . ($list->md_amount_type == "Charge Percent" ? "selected" : "") . ' value="Charge Percent">Charge Percent</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" id="' . $list->id . '_md_value" name="md_value[]" value="' . $list->md_amount_value . '" required="required">
                </td>


                <td>
                    <select class="form-select" aria-label="Default select example" id="' . $list->id . '_dt_comtype" name="dt_comtype[]">
                        <option ' . ($list->dt_amount_type == "Commission Flat" ? "selected" : "") . ' value="Commission Flat">Commission Flat</option>
                        <option ' . ($list->dt_amount_type == "Commission Percent" ? "selected" : "") . ' value="Commission Percent">Commission Percent</option>
                        <option ' . ($list->dt_amount_type == "Charge Flat" ? "selected" : "") . ' value="Charge Flat">Charge Flat</option>
                        <option ' . ($list->dt_amount_type == "Charge Percent" ? "selected" : "") . ' value="Charge Percent">Charge Percent</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" id="' . $list->id . '_dt_value" name="dt_value[]" value="' . $list->dt_amount_value . '" required="required">
                </td>

                <td>
                    <select class="form-select" aria-label="Default select example" id="' . $list->id . '_rt_comtype" name="rt_comtype[]">
                        <option ' . ($list->rt_amount_type == "Commission Flat" ? "selected" : "") . ' value="Commission Flat">Commission Flat</option>
                        <option ' . ($list->rt_amount_type == "Commission Percent" ? "selected" : "") . ' value="Commission Percent">Commission Percent</option>
                        <option ' . ($list->rt_amount_type == "Charge Flat" ? "selected" : "") . ' value="Charge Flat">Charge Flat</option>
                        <option ' . ($list->rt_amount_type == "Charge Percent" ? "selected" : "") . ' value="Charge Percent">Charge Percent</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" id="' . $list->id . '_rt_value" name="rt_value[]" value="' . $list->rt_amount_value . '" required="required">
                </td>
                <td>
                    <select class="form-select" aria-label="Default select example" id="' . $list->id . '_ap_comtype" name="ap_comtype[]">
                        <option ' . ($list->ap_amount_type == "Commission Flat" ? "selected" : "") . ' value="Commission Flat">Commission Flat</option>
                        <option ' . ($list->ap_amount_type == "Commission Percent" ? "selected" : "") . ' value="Commission Percent">Commission Percent</option>
                        <option ' . ($list->ap_amount_type == "Charge Flat" ? "selected" : "") . ' value="Charge Flat">Charge Flat</option>
                        <option ' . ($list->ap_amount_type == "Charge Percent" ? "selected" : "") . ' value="Charge Percent">Charge Percent</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" id="' . $list->id . '_ap_value" name="ap_value[]" value="' . $list->ap_amount_value . '" required="required">
                </td>
                <td>
                    <button class="btn btn-primary waves-effect waves-light updateCommission" id="' . $list->id . '">Update</button>
                    <a href="' . url('admin/commission/denomination') . '?scheme_id=' . $post->id . '&provider_id=' . $list->id . '&service_id=' . $post->service . '" class="btn btn-outline-info waves-effect waves-light mt-1">Denom</a>
                </td>   
              </tr>';
                $i++;
            }
            $output .= '</tbody></table>';
            echo $output;
        } else {
            echo '<h4 class="text-center text-secondary my-3">No record found</h4>';
        }
        //return $providers;
    }



    public function updateData(Request $post)
    {
        $rules = array(
            'schemeName' => 'required|string|max:100|regex:/^[A-Za-z0-9\s\-]+$/',
            'status' => 'required|numeric|digits:1',
        );

        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json(
                array(
                    'type' => 'error',
                    'message' => $error
                )
            );
        }

        try {
            $newSchemeId = null;
            $isNew = ((int) $post->edit_id) === 0;
            if ($isNew) {
                $row = [
                    'scheme_name' => trim((string) $post->schemeName),
                    'status' => (int) $post->status,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
                if (Schema::hasColumn('schemes', 'deleted_at')) {
                    $row['deleted_at'] = 0;
                }
                if (Schema::hasColumn('schemes', 'user_id')) {
                    $row['user_id'] = (int) (Session::get('user_id') ?: 1);
                }
                $newSchemeId = DB::table('schemes')->insertGetId($row);
                $update = $newSchemeId ? true : false;
                $message = "Create sucessfuly";
            } else {
                $update = DB::table('schemes')->where('id', $post->edit_id)->update([
                    'scheme_name' => trim((string) $post->schemeName),
                    'status' => (int) $post->status,
                    'updated_at' => Carbon::now()
                ]);
                $message = "Update sucessfuly";
            }
            if ($update) {
                return response()->json([
                    'type' => 'success',
                    'message' => $message,
                    'id' => $newSchemeId ?? $post->edit_id,
                    'scheme_name' => $post->schemeName,
                ]);
            }

            return response()->json([
                'type' => 'error',
                'message' => 'Unable to save scheme. Please try again.',
            ]);
        } catch (\Throwable $e) {
            \Log::warning('schemeUpdate failed: '.$e->getMessage());
            return response()->json([
                'type' => 'error',
                'message' => 'Unable to create scheme: '.$e->getMessage(),
            ]);
        }
    }


    public function SingleUpdateCommission(Request $post)
    {
        $rules = array(
            'provider_id' => 'required',
            'scheme_id' => 'required',
            'md_comtype' => 'required|in:Commission Flat,Commission Percent,Charge Flat,Charge Percent',
            'md_value' => 'required',
            'dt_comtype' => 'required|in:Commission Flat,Commission Percent,Charge Flat,Charge Percent',
            'dt_value' => 'required',
            'rt_comtype' => 'required|in:Commission Flat,Commission Percent,Charge Flat,Charge Percent',
            'rt_value' => 'required',
            'ap_comtype' => 'required|in:Commission Flat,Commission Percent,Charge Flat,Charge Percent',
            'ap_value' => 'required',
        );

        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json(
                array(
                    'type' => 'error',
                    'message' => $error
                )
            );
        }


        try {
            $this->ensureApiPartnerCommissionColumns();
            $existing = DB::table('scheme_commissions')
                ->where('scheme_id', $post->scheme_id)
                ->where('provider_id', $post->provider_id)
                ->first();
            DB::table('scheme_commissions')->updateOrInsert(
                [
                    'scheme_id' => $post->scheme_id,
                    'provider_id' => $post->provider_id,
                ],
                [
                    'provider_id' => $post->provider_id,
                    'scheme_id' => $post->scheme_id,
                    'wt_amount_type' => $post->wt_comtype ?: ($existing->wt_amount_type ?? 'Commission Flat'),
                    'wt_amount_value' => $post->filled('wt_value') ? $post->wt_value : ($existing->wt_amount_value ?? 0),
                    'md_amount_type' => $post->md_comtype,
                    'md_amount_value' => $post->md_value,
                    'dt_amount_type' => $post->dt_comtype,
                    'dt_amount_value' => $post->dt_value,
                    'rt_amount_type' => $post->rt_comtype,
                    'rt_amount_value' => $post->rt_value,
                    'ap_amount_type' => $post->ap_comtype,
                    'ap_amount_value' => $post->ap_value,
                ]
            );
            $message = "Update sucessfuly";
            $data['type'] = 'success';
            $data['message'] = $message;
        } catch (\Throwable $th) {
            $data['type'] = 'error';
            $data['message'] = $th->errorInfo;
        }
        return $data;
        //echo "<pre>";print_r($post->all());die;
    }

    public function BulkUpdateCommission(Request $post)
    {
        $this->ensureApiPartnerCommissionColumns();
        foreach ($post->provider_id as $k => $v) {
            $existing = DB::table('scheme_commissions')
                ->where('scheme_id', $post->scheme_id)
                ->where('provider_id', $post->provider_id[$k])
                ->first();
            DB::table('scheme_commissions')->updateOrInsert(
                [
                    'scheme_id' => $post->scheme_id,
                    'provider_id' => $post->provider_id[$k],
                ],
                [
                    'provider_id' => $post->provider_id[$k],
                    'scheme_id' => $post->scheme_id,
                    'wt_amount_type' => $post->wt_comtype[$k] ?? ($existing->wt_amount_type ?? 'Commission Flat'),
                    'wt_amount_value' => $post->wt_value[$k] ?? ($existing->wt_amount_value ?? 0),
                    'md_amount_type' => $post->md_comtype[$k],
                    'md_amount_value' => $post->md_value[$k],
                    'dt_amount_type' => $post->dt_comtype[$k],
                    'dt_amount_value' => $post->dt_value[$k],
                    'rt_amount_type' => $post->rt_comtype[$k],
                    'rt_amount_value' => $post->rt_value[$k],
                    'ap_amount_type' => $post->ap_comtype[$k] ?? 'Commission Flat',
                    'ap_amount_value' => $post->ap_value[$k] ?? 0,
                ]
            );
        }
        $message = "Update sucessfuly";
        $data['type'] = 'success';
        $data['message'] = $message;
        return $data;
    }

    private function ensureApiPartnerCommissionColumns(): void
    {
        ensure_api_partner_commission_columns();
    }
}