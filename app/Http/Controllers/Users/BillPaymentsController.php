<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use DateTime;
use Session;
use Log;
class BillPaymentsController extends Controller
{
   
    public function index(Request $post)
    { 
        
        // foreach ($json as $key => $list) {
        //     echo "<pre>";print_r($list);"<br>";
        //     $idd = DB::table('bbps_operator_params')->insertGetId([
        //         'biller_id' => $list['biller_id'],
        //         'category_id' => $list['category_id'],
        //         'biller_data' => $list['biller_data']
        //     ]);
        // }
            
        // return $json[0]['biller_id'];

        // $data = DB::table('bbps_operator')->where("category_id",25)->get();
        // foreach ($data as $key => $list) {
            
        //     $provider_logo = "provider_logo.png";
            
        //     $id = DB::table('providers')->insertGetId([
        //         'provider_name' => $list->biller_name,
        //         'service_id' => 17,
        //         'api_id' => 0,
        //         'backup_api_id' => 0,
        //         'backup_api2_id' => 0,
        //         'backup_api3_id' => 0,
        //         'minium_amount' => 10,
        //         'maxium_amount' => 100000,
        //         'provider_down' => 0,
        //         'amount_type' => "Commission Percent",
        //         'amount_value' => 1,
        //         'provider_logo' => $provider_logo,
        //         'status' => 1,
        //         'created_at' => Carbon::now(),
        //         'updated_at' => Carbon::now()
        //     ]);
        //     $idd = DB::table('api_provider_codes')->insertGetId([
        //         'provider_id' => $id,
        //         'api_id' => 22,
        //         'provider_code' => $list->biller_id,
        //         'created_at' => Carbon::now(),
        //         'updated_at' => Carbon::now()
        //     ]);

        // }
        
        // return 1;

        // $parameters = "";
        // $order_id = "RC" . date("YmdHis") . rand(11111, 999999) . rand(1, 3) . rand(3, 6) . rand(6, 9);
        // $result = \helpers::curl($url, 'GET', $parameters, $header, "yes", "BILL_INFO", $order_id);
        // return $result['response']; 

        //$api = DB::table('apis')->where('id', '6')->first();

        //$url = 'https://planapi.in/Api/Mobile/BBPSBillInfo?apimember_id=' . $api->api_username . '&api_password=' . $api->api_password . '&operator_code=46&Accountno=911212240041';
        // $url = 'https://www.mplan.in/api/electricinfo.php?apikey=' . $api->api_key . '&offer=roffer&tel=911212240012&operator=WESCO';
        // $header = [];
        // $parameters = "";
        // $order_id = "RC" . date("YmdHis") . rand(11111, 999999) . rand(1, 3) . rand(3, 6) . rand(6, 9);
        // $result = \helpers::curl($url, 'GET', $parameters, $header, "yes", "BILL_INFO", $order_id);
        // return $result['response']; 
        if(!$post->id){
            $post->id = 3;
        }
        $data['providers'] = DB::table('providers')->select('id', 'provider_name')->where('service_id', $post->id)->where('deleted_at', '!=', 1)->where('status', 1)->get();
        $service = DB::table('services')->where("id",$post->id)->where("deleted_at",0)->first();
        $data['service'] = $service->service_name;
        return view('users.services.bill-payments',$data);
    }

    public function fetchProviderParams(Request $post){
        $rules = array(

            'id' => 'required|numeric',

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
        $provider_code = \helpers::ApiProviderCode(22, $post->id);
        if($provider_code){
            $biller = DB::table('bbps_operator_params')->select('id', 'biller_data')->where('biller_id', $provider_code)->first();
            if($biller){
                return response()->json(array(

                    'type' => 'success',
    
                    'message' => "Get sucessfuly",
                    'biller' => $biller
    
                ));
            }else{
                return response()->json(array(
    
                    'type' => 'error',
    
                    'message' => "Something went wrong!"
    
                ));
            }
        }else{
            return response()->json(array(

                'type' => 'error',

                'message' => "Something went wrong!"

            ));
        }
        
    }


}
