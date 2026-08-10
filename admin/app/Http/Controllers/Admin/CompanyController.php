<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class CompanyController extends Controller
{
    public function index(Request $post)
    {
        return view('admin.company.manage-company');
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
        $total_row = DB::table('companies')->where('deleted_at', '!=', 1)->get();
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

        $list = DB::table('companies')->where('deleted_at', '!=' , 1)->orderBy('id', 'DESC')->offset($start)->limit($limit)->get();
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
                <th>Company Name</th>
                <th>Domain</th>
                <th>Support No</th>
                <th>Support No Alt</th>
                <th>Support Email</th>
                <th>Company Address</th>
                <th>Status</th>
                <th>Action</th>
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
                <td><img src="'.admin_company_logo($list->company_icon).'" class="avatar-xs rounded-3 me-2"></td>
                <td>' . $list->company_name . '</td>
                <td>' . $list->domain . '</td>
                <td>' . $list->support_number . '</td>
                <td>' . $list->support_number_2 . '</td>
                <td>' . $list->support_email . '</td>
                <td>' . $list->company_address . '</td>
                <td>' . $status . '</td>
                <td>
                    <a id="' . $list->id . '" class="badge text-bg-secondary editDetails"><i class="ri-pencil-fill align-bottom"></i> Edit</a>
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
        $get = DB::table('companies')->where('id', $post->id)->first();
        if($get){
            $data['type'] = 'success';
            $data['message'] = "Get sucessfuly";
            $data['data'] = $get;
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        return $data;
    }


    public function updateData(Request $post)
    {
        $rules = array(
            'edit_id'  => 'required|numeric|min:1',
            'domain'  => 'required',
            'company_name'  => 'required',
            'support_number'  => 'required',
            'support_number_2'  => 'required',
            'support_email'  => 'required',
            'company_address'  => 'required',
            'self_register'  => 'required|numeric',
            'email_message'  => 'required|numeric',
            'company_logo' => 'mimes:jpeg,jpg,png|max:10000',
            'company_icon' => 'mimes:jpeg,jpg,png|max:10000',
            'invoice_logo' => 'mimes:jpeg,jpg,png|max:10000',
            'apk_file_name'  => 'required',
            'payment_gateway'  => 'required|numeric',
            'payment_gateway_min'  => 'required|numeric|min:1',
            'payment_gateway_max'  => 'required|numeric|min:1',
            'payment_gateway_key'  => 'required',

            'payment_gateway2'  => 'required|numeric',
            'payment_gateway2_min'  => 'required|numeric|min:1',
            'payment_gateway2_max'  => 'required|numeric|min:1',
            'payment_gateway2_key'  => 'required',
            
            'sms_api_method'  => 'required',
            'sms_request_url'  => 'required',
            'whatsapp_api_method'  => 'required',
            'whatsapp_request_url'  => 'required',
            'meta_kewords'  => 'required',
            'refund_policy'  => 'required',
            'privacy_policy'  => 'required',
            'terms_and_conditions'  => 'required',
            'header_value'  => 'required',
            'footer_value'  => 'required',
        );

        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json(array(
                'type' => 'error',  
                'message' => $error
            ));
        }
      // return $post->all();
        if($post->edit_id==0){
            $data['type'] = 'error';
            $data['message'] = "Not Alllowed.";
            return $data;
        }else{
            if($post->company_logo){
                $companyLogo = "companyLogo".csrf_token().time().'.'.$post->company_logo->extension();  
                $post->company_logo->move(public_path('company_logo'), $companyLogo);
            }else{
                $companyLogo =  $post->old_company_logo;
            }

            if($post->company_icon){
                $companyIcon = "companyIcon".csrf_token().time().'.'.$post->company_icon->extension();  
                $post->company_icon->move(public_path('company_logo'), $companyIcon);
            }else{
                $companyIcon =  $post->old_company_icon;
            }

            if($post->invoice_logo){
                $invoiceLogo = "invoiceLogo".csrf_token().time().'.'.$post->invoice_logo->extension();  
                $post->invoice_logo->move(public_path('company_logo'), $invoiceLogo);
            }else{
                $invoiceLogo =  $post->old_invoice_logo;
            }

            $update = DB::table('companies')->whereIn('id', [1,2])->update([
                //'domain' => $post->domain,
                'company_name' => $post->company_name,
                'support_number' => $post->support_number,
                'support_number_2' => $post->support_number_2,
                'support_email' => $post->support_email,
                'company_address' => $post->company_address,
                'self_register' => $post->self_register,
                'email_message' => $post->email_message,
                'company_logo' => $companyLogo,
                'company_icon' => $companyIcon,
                'invoice_logo' => $invoiceLogo,
                'apk_file_name' => $post->apk_file_name,
                'payment_gateway' => $post->payment_gateway,
                'payment_gateway_min' => $post->payment_gateway_min,
                'payment_gateway_max' => $post->payment_gateway_max,
                'payment_gateway_key' => $post->payment_gateway_key,
                'payment_gateway2' => $post->payment_gateway2,
                'payment_gateway2_min' => $post->payment_gateway2_min,
                'payment_gateway2_max' => $post->payment_gateway2_max,
                'payment_gateway2_key' => $post->payment_gateway2_key,
                'sms_api_method' => $post->sms_api_method,
                'sms_request_url' => $post->sms_request_url,
                'whatsapp_api_method' => $post->whatsapp_api_method,
                'whatsapp_request_url' => $post->whatsapp_request_url,
                'meta_kewords' => $post->meta_kewords,
                'refund_policy' => $post->refund_policy,
                'privacy_policy' => $post->privacy_policy,
                'terms_and_conditions' => $post->terms_and_conditions,
                'header_value' => $post->header_value,
                'footer_value' => $post->footer_value,
                'updated_at' => Carbon::now()
            ]);
            $message = "Update sucessfuly";
        }
        if($update){
            $data['type'] = 'success';
            $data['message'] =  $message;
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        return $data;
    }
}
