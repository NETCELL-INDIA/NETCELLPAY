<?php
//namespace App\Http\Controllers\Api;
use App\Http\Middleware\AuthKeyCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BankController;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\DpayController;
use App\Http\Controllers\AndroidApp\UserController;
use App\Http\Controllers\AndroidApp\ReportController;
use App\Http\Controllers\AndroidApp\RechargeController;
use App\Http\Controllers\BillPayController;
// use App\Http\Controllers\OrderController;
use App\Http\Controllers\UpiGatewayController;
use App\Http\Controllers\BbpsController;
use App\Http\Controllers\AndroidApp\BillavenueController;
use App\Http\Controllers\PhonepeGatewayController;
use App\Http\Controllers\CcavenueGatewayController;
use App\Http\Controllers\OneGatewayController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/////Phonepe Gateway Routes Start
Route::any('/v1/phonepe/payment/status',[PhonepeGatewayController::class,'status']);
Route::any('/v1/phonepe/payment/callback',[PhonepeGatewayController::class,'callback']);
Route::any('/v1/phonepe/payment/cronjob',[PhonepeGatewayController::class,'cronjob']);
////Phonepe Gateway Routes End


/////Ccavenue Gateway Routes Start
Route::any('/v1/ccavenue/payment/status',[CcavenueGatewayController::class,'status']);
Route::any('/v1/ccavenue/payment/callback',[CcavenueGatewayController::class,'callback']);
Route::any('/v1/ccavenue/payment/cancel',[CcavenueGatewayController::class,'status']);
Route::any('/v1/ccavenue/payment/cronjob',[CcavenueGatewayController::class,'cronjob']);
////Ccavenue Gateway Routes End

/////Upi Gateway Routes Start
Route::any('upi_gateway_status',[UpiGatewayController::class,'status']);
Route::any('upi_gateway/call_back',[UpiGatewayController::class,'callBack']);
////Upi Gateway Routes End

/////Upi Gateway Routes Start
Route::any('one_gateway_status',[OneGatewayController::class,'status']);
Route::any('one_gateway/call_back',[OneGatewayController::class,'callBack']);
////Upi Gateway Routes End

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
// Route::post('payment/status',[OrderController::class,'paymentCallback']);
///
Route::get('get/banks/{id}',[BankController::class,'banks_list']);

//// BBPS Service Start

Route::any('bbps/service',[BillavenueController::class,'index']);


// //Route::get('bbps/generateToken',[BbpsController::class,'generateToken']);
// //https://uat-accounts.payu.in/
// Route::get('bbps/generateToken',[BbpsController::class,'generateToken']);
// //https://bbps-sb.payu.in/payu-nbc/v1/nbc/getAllBillerCategory
// Route::get('bbps/getAllBillerCategory',[BbpsController::class,'getAllBillerCategory']);
// //https://bbps-sb.payu.in/payu-nbc/v1/nbc/getAgentBalance?agentId=2
// //Route::get('bbps/getAgentBalance',[BbpsController::class,'getAgentBalance']);
// //https://bbps-sb.payu.in/payu-nbc/v1/nbc/getBillerByBillerCategory?billerCategoryName=LOAN
// Route::get('bbps/getBillerByBillerCategory/{categoryName}',[BbpsController::class,'getBillerByBillerCategory']);
// //https://bbps-sb.payu.in/payu-nbc/v1/nbc/getRegions
// Route::get('bbps/getRegions',[BbpsController::class,'getRegions']);
// //https://bbps-sb.payu.in/payu-nbc/v1/nbc/getBillerByRegion?region=Delhi
// Route::get('bbps/getBillerByRegion/{region}',[BbpsController::class,'getBillerByRegion']);
// //https://bbps-sb.payu.in/payu-nbc/v1/nbc/billfetchrequest
// Route::any('bbps/billfetchrequest',[BbpsController::class,'billfetchrequest']);


//// BBPS Service End

//// Api Service Start
Route::get('api_service/check_balance',[ApiController::class,'check_balance']);
Route::get('api_service/check_status',[ApiController::class,'check_status']);
Route::get('api_service/recharge',[ApiController::class,'recharge']);
Route::any('api_service/call_back',[ApiController::class,'call_back']);
Route::post('api_service/recharge',[ApiController::class,'recharge']);
//// Api Service End

Route::group(['middleware' => AuthKeyCheck::class], function () {
    ////Direct Pay Api Service Start
    Route::post('android_app/ipay/validate-recharge',[DpayController::class,'validateRecharge']);
    Route::post('android_app/ipay/check-status',[DpayController::class,'statusCheck']);
    ////Direct Pay Api Service End

    //// Android App Api Service Start
    Route::post('android_app/login',[UserController::class,'login']);
    Route::post('android_app/home_data',[UserController::class,'home_data']);
    Route::post('android_app/my_profile',[UserController::class,'my_profile']);
    ///Kyc Api Start
    Route::post('android_app/aadhaarKycSendOtp',[UserController::class,'aadhaarKycSendOtp']);
    Route::post('android_app/aadhaarKycSubmitOtp',[UserController::class,'aadhaarKycSubmitOtp']);
    ///Kyc Api End
    Route::post('android_app/recharge_report',[ReportController::class,'recharge_report']);
    Route::post('android_app/send_complaint_submit',[ReportController::class,'send_complaint_submit']);
    Route::post('android_app/wallet_fund_report',[ReportController::class,'wallet_fund_report']);
    Route::post('android_app/account_reports',[ReportController::class,'account_reports']);
    Route::post('android_app/fund_request_reports',[ReportController::class,'fund_request_reports']);
    Route::post('android_app/complaint_reports',[ReportController::class,'complaint_reports']);
    Route::post('android_app/day_book_reports',[ReportController::class,'day_book_reports']);
    Route::post('android_app/my_commission',[UserController::class,'my_commission']);
    Route::post('android_app/bank_details',[UserController::class,'bank_details']);
    Route::post('android_app/fund_request',[ReportController::class,'fund_request']);
    Route::post('android_app/change_password',[UserController::class,'change_password']);
    Route::post('android_app/company_data',[UserController::class,'company_data']);
    Route::post('android_app/provider',[RechargeController::class,'provider']);
    Route::post('android_app/check_mobile_number',[RechargeController::class,'check_mobile_number']);
    Route::post('android_app/check_roffer_fatch',[RechargeController::class,'check_roffer_fatch']);
    Route::post('android_app/check_browse_plan',[RechargeController::class,'check_browse_plan']);
    Route::post('android_app/check_dth_info',[RechargeController::class,'check_dth_info']);
    Route::post('android_app/check_bill_info',[BillPayController::class,'bill_fatch']);
    Route::post('android_app/check_fastag_info',[BillPayController::class,'fastag_fatch']);
    Route::post('android_app/forget_password_send',[UserController::class,'forget_password_send']);
    Route::post('android_app/forget_password_submit',[UserController::class,'forget_password_submit']);
    Route::post('android_app/roles',[UserController::class,'roles']);
    Route::post('android_app/register_submit',[UserController::class,'register_submit']);
    Route::post('android_app/upi_log',[ReportController::class,'upi_log']);
    Route::post('android_app/upi_check',[ReportController::class,'upi_check']);
    Route::get('android_app/upi_callback',[ReportController::class,'upi_callback']);
    //// Android App Api Service End
    //// Android App Api Distributer Start

    Route::post('android_app/user_list',[UserController::class,'user_list']);
    Route::post('android_app/scheme_list',[UserController::class,'scheme_list']);
    Route::post('android_app/add_user',[UserController::class,'add_user']);
    Route::post('android_app/fund_trasfer',[ReportController::class,'fund_trasfer']);
    //// Android App Api Distributer End

    ///Login By Otp Api Start
    Route::any('android_app/sendOtp',[UserController::class,'sendOtp']);
    Route::any('android_app/loginOtp',[UserController::class,'loginOtp']);
    Route::any('android_app/registerOtp',[UserController::class,'registerOtp']);
    Route::any('android_app/registerOtpSubmit',[UserController::class,'registerOtpSubmit']);
    ///Login By Otp Api End
});