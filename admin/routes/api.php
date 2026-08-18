<?php
//namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\BankController;
// Legacy AndroidApp / Api controllers are not shipped in this repo; mobile APIs live in the root app (routes/api.php).
// use App\Http\Controllers\Api\ApiController;
// use App\Http\Controllers\Api\DpayController;
// use App\Http\Controllers\AndroidApp\UserController;
// use App\Http\Controllers\AndroidApp\ReportController;
// use App\Http\Controllers\AndroidApp\RechargeController;
// use App\Http\Controllers\BillPayController;
// use App\Http\Controllers\OrderController;
use App\Http\Controllers\UpiGatewayController;
// use App\Http\Controllers\BbpsController;
// use App\Http\Controllers\AndroidApp\BillavenueController;
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

/*
//// BBPS Service Start — disabled: BillavenueController not present in admin app
Route::any('bbps/service',[BillavenueController::class,'index']);
//// BBPS Service End

//// Api Service Start — disabled: Api\ApiController not present in admin app
Route::get('api_service/check_balance',[ApiController::class,'check_balance']);
Route::get('api_service/check_status',[ApiController::class,'check_status']);
Route::get('api_service/recharge',[ApiController::class,'recharge']);
Route::any('api_service/call_back',[ApiController::class,'call_back']);
Route::post('api_service/recharge',[ApiController::class,'recharge']);
//// Api Service End

Route::group(['middleware' => AuthKeyCheck::class], function () {
    //// Android App legacy APIs — use root app /v1/* routes instead
    ...
});
*/
