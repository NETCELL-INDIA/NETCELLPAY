<?php
////////ApiPartner APis Controllers Start
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiPartner\ApiController;
use App\Http\Controllers\Users\RechargeController;
use App\Http\Controllers\Users\addMoneyController;
use App\Http\Controllers\Users\allUpiMoneyController;
use App\Http\Middleware\ApiPartnerCheck;
use App\Http\Middleware\AppUserCheck;
////App APis Controllers Start
use App\Http\Controllers\AppV1\AuthController;
use App\Http\Controllers\AppV1\ReportsController;
use App\Http\Controllers\AppV1\ParentController;
use Illuminate\Support\Facades\Redirect;

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
Route::any('/all-upi-add-money/redirect-status', function (Request $post) {
    //return route('allUpiAddMoneyCheckStatus',$post->order_id);
    return Redirect::to(route('allUpiAddMoneyCheckStatus',$post->order_id));
})->name('ApiRedirectAllUpiGateway');

Route::any('add-money/callback-status',[addMoneyController::class,'AddMoneyCallback'])->name('AddMoneyCallback');
Route::any('all-upi-add-money/callback-status',[allUpiMoneyController::class,'AddMoneyCallback'])->name('AllUpiAddMoneyCallback');

Route::group(['middleware' => ApiPartnerCheck::class], function () {
    Route::get('/ApiPartner/V1/Recharge',[RechargeController::class,'rechargeCall']);
    Route::get('/ApiPartner/V1/RechargeStatus',[RechargeController::class,'getRechargeReciept']);
    Route::get('/ApiPartner/V1/RechargeComplaint',[RechargeController::class,'submitRechargeComplaint']);
});



Route::post('/v1/login',[AuthController::class,'LoginCheck']);
Route::post('/v1/login-otp',[AuthController::class,'checkLoginOtp']);
////Register Apis
Route::post('/v1/create-account',[AuthController::class,'sendOtpUserRegister']);
Route::post('/v1/create-account-otp',[AuthController::class,'verifyOtpUserRegister']);
///Reset Password Apis
Route::post('/v1/reset-password',[AuthController::class,'resetPassword']);
Route::post('/v1/reset-password-otp',[AuthController::class,'resetPasswordOtp']);

Route::group(['middleware' => AppUserCheck::class], function () {
    /////
    Route::post('/v1/home',[AuthController::class,'homeData']);
    Route::post('/v1/my-profile',[AuthController::class,'myProfile']);
    Route::post('/v1/my-commission',[AuthController::class,'myCommission']);
    Route::post('/v1/change-password',[AuthController::class,'myProfilePasswordChange']);
    Route::post('/v1/change-pin',[AuthController::class,'myProfilePinChange']);
    Route::post('/v1/generate-pin',[AuthController::class,'generatePin']);
    ///////
    Route::post('/v1/check-number',[RechargeController::class,'RechargeCheckMobile']);
    Route::post('/v1/check-roffer',[RechargeController::class,'RechargeCheckRofferFatch']);
    Route::post('/v1/check-view-plan',[RechargeController::class,'RechargeCheckPlanFatch']);
    Route::post('/v1/run-recharge-api',[RechargeController::class,'rechargeCall']);
    Route::post('/v1/recharge-reciept',[RechargeController::class,'getRechargeReciept']);
    Route::post('/v1/dth-info',[RechargeController::class,'dthInfo']);
    Route::post('/v1/dth-heavy-refresh',[RechargeController::class,'dthHeavyRefresh']);
    Route::post('/v1/instant-add-money',[addMoneyController::class,'addMoneyRequestSubmit']);
    Route::post('/v1/instant-add-money-qr-code',[allUpiMoneyController::class,'addMoneyRequestSubmit']);
    Route::post('/v1/send-complaint',[RechargeController::class,'submitRechargeComplaint']);
    ///////
    Route::post('/v1/recharge-reports',[ReportsController::class,'fetchAllRecharge']);
    Route::post('/v1/account-reports',[ReportsController::class,'fetchAllAccount']);
    Route::post('/v1/fund-reports',[ReportsController::class,'fetchAllFund']);
    Route::post('/v1/complaint-reports',[ReportsController::class,'fetchAllComplaints']);
    Route::post('/v1/fund-request-reports',[ReportsController::class,'fetchAllFundRequest']);
    Route::post('/v1/bill-payment-reports',[ReportsController::class,'fetchAllBillPayment']);
    Route::post('/v1/money-transfer-reports',[ReportsController::class,'fetchAllMoneyTransfer']);
    Route::post('/v1/daybook-reports',[ReportsController::class,'daybookReports']);


    //////
    Route::post('/v1/user-list',[ParentController::class,'fetchAll']);
    Route::post('/v1/bank-list',[ParentController::class,'bankList']);
    Route::post('/v1/add-user',[ParentController::class,'userlistUpdate']);
    Route::post('/v1/fund-transfer',[ParentController::class,'fundUpdate']);
    Route::post('/v1/search-user-list',[ParentController::class,'userListSearchUser']);
    Route::post('/v1/search-user-list-name',[ParentController::class,'userListSearchUserName']);
    //////
});
//Route::get('/ApiPartner/V1/Recharge',[RechargeController::class,'mobileIndex'])->middleware('api_partner_check');



// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
