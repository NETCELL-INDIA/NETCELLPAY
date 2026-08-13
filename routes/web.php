<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Users\AuthController;
use App\Http\Controllers\Users\DashboardController;
use App\Http\Controllers\Users\UserListController;
use App\Http\Controllers\Users\FundController;
use App\Http\Controllers\Users\UserFundRequstController;
use App\Http\Controllers\Users\FundReportsController;
use App\Http\Controllers\Users\RechargeController;
use App\Http\Controllers\Users\AccountReportsController;
use App\Http\Controllers\Users\RechargeReportsController;
use App\Http\Controllers\Users\ComplaintController;
use App\Http\Controllers\Users\ProfileController;
use App\Http\Controllers\Users\ApiSettingsController;
use App\Http\Controllers\Users\addMoneyController;
use App\Http\Controllers\Users\allUpiMoneyController;
use App\Http\Controllers\Users\BankController;
use App\Http\Controllers\Users\CallBackController;
use App\Http\Controllers\Users\BillPaymentsController;
use App\Http\Middleware\UserCheck;
use App\Http\Controllers\Users\LoginHistoryController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendEmail;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//Clear route cache
Route::get('/cron-job', function() {
    \Artisan::call('send_sms_every_minutes');
    \Artisan::call('recharge_callback');
    \Artisan::call('process_pending_recharges');
    \Artisan::call('queue:work', ['--stop-when-empty' => true]);
    return "done";
});
//Route::get('add-list-shiba',[AuthController::class,'add_user_by_arrray']);
// Route::get('/cron-job', function() {
//     \Artisan::call('send_sms_every_minutes');
//     \Artisan::call('recharge_callback');
//     \Artisan::call('queue:work');
//     return 1;
//     // $subject = "Shiba";
//     // $message = "hii";
//     //\Artisan::call('make:mail SendEmail');
//     //\Artisan::call('queue:work');
//   //Mail::to('shibatechnology@gmail.com')->queue(new SendEmail($subject,$message));
// });
Route::get('/optimize', function() {
    \Artisan::call('complaint_callback');
    //\Artisan::call('recharge_callback');
    //\Artisan::call('make:command ComplaintCallback --command=complaint_callback');
    //\Artisan::call('make:job RechargeJob');
    \Artisan::call('route:cache');
    \Artisan::call('config:cache');
    \Artisan::call('cache:clear');
    \Artisan::call('view:clear');
    \Artisan::call('optimize:clear');
    return 'software optimize';
});
Route::get('/job', function() {
    // $return = \Artisan::call('send_sms_every_minutes');
    // return $return;
});
////Website Start
$websiteCompany = function () {
    $company = DB::table('companies')
        ->where('status', '1')
        ->where('domain', request()->getHost())
        ->first();

    $company = $company ?: DB::table('companies')
        ->where('status', '1')
        ->first();

    abort_unless($company, 503, 'Website configuration is not available.');

    return $company;
};

Route::get('/', function () use ($websiteCompany) {
    return view('users.website.welcome', ['company' => $websiteCompany()]);
})->name('website.home');

Route::get('/home', function () use ($websiteCompany) {
    return view('users.website.welcome', ['company' => $websiteCompany()]);
});

Route::get('/about-us', function () use ($websiteCompany) {
    return view('users.website.about-us', ['company' => $websiteCompany()]);
});

Route::get('/services', function () use ($websiteCompany) {
    return view('users.website.services', ['company' => $websiteCompany()]);
});

Route::get('/contact-us', function () use ($websiteCompany) {
    return view('users.website.contact-us', ['company' => $websiteCompany()]);
});

Route::get('/privacy-policy', function () use ($websiteCompany) {
    return view('users.website.privacy-policy', ['company' => $websiteCompany()]);
});

Route::get('/term-and-condition', function () use ($websiteCompany) {
    return view('users.website.term-and-condition', ['company' => $websiteCompany()]);
});

Route::get('/refunds', function () use ($websiteCompany) {
    return view('users.website.refunds', ['company' => $websiteCompany()]);
});
////Website End
///All Callback Url Start
///Route::any('add-money/callback-status',[addMoneyController::class,'AddMoneyCallback'])->name('AddMoneyCallback');

Route::any('recharge-callback/{api_id}',[CallBackController::class,'rechargeCallback'])->name('rechargeCallback');

Route::post('users/fund/add-money-status/{order_id}',[addMoneyController::class,'AddMoneyStatus'])->name('AddMoneyStatus');
Route::get('users/fund/add-money-check-status/{order_id}',[addMoneyController::class,'AddMoneyCheckStatus'])->name('AddMoneyCheckStatus');
/////
Route::get('users/fund/qr-code-add-money-check-status/{order_id}',[allUpiMoneyController::class,'AddMoneyCheckStatus'])->name('allUpiAddMoneyCheckStatus');
Route::post('users/fund/qr-code-add-money-status/{order_id}',[allUpiMoneyController::class,'AddMoneyStatus'])->name('allUpiAddMoneyStatus');
///All Callback Url End
//users Portal Routes 
Route::get('users', function () {
    $user = DB::table('users')
        ->where('id', session('user_id'))
        ->where('login_key', session('login_key'))
        ->whereNotIn('role_id', [1, 2])
        ->first();

    if ($user && (int) $user->status === 1) {
        return redirect('users/dashboard');
    }

    return redirect('users/login');
});
Route::get('users/login',[AuthController::class,'Login']);
Route::post('users/login-check',[AuthController::class,'LoginCheck'])->name('LoginCheck');
Route::post('users/check-otp-login',[AuthController::class,'checkLoginOtp'])->name('checkLoginOtp');
///
Route::get('users/forgot-password',[AuthController::class,'forgotPassword'])->name('forgotPassword');
Route::post('users/send-otp-forgot-password',[AuthController::class,'sendOtpForgotPassword'])->name('sendOtpForgotPassword');
Route::post('users/verify-otp-forgot-password',[AuthController::class,'verifyOtpForgotPassword'])->name('verifyOtpForgotPassword');
////
Route::get('users/register',[AuthController::class,'userRegister'])->name('userRegister');
Route::post('users/send-otp-register',[AuthController::class,'sendOtpUserRegister'])->name('sendOtpUserRegister');
Route::post('users/verify-otp-register',[AuthController::class,'verifyOtpUserRegister'])->name('verifyOtpUserRegister');
//users Portal Routes usersCheck(middleware)
Route::group(['middleware' => userCheck::class], function () {

    //My Profile Recharge Routes
    Route::get('users/profile/logout',[AuthController::class,'Logout'])->name('usersLogout');
    Route::get('users/api-settings', [ApiSettingsController::class, 'index'])->name('apiSettings');
    Route::post('users/api-settings/save', [ApiSettingsController::class, 'store'])->name('apiSettingsStore');
    Route::get('users/profile/my-profile',[ProfileController::class,'myProfile'])->name('myProfile');
    Route::get('users/profile/my-profile-data',[ProfileController::class,'myProfileData'])->name('myProfileData');
    Route::post('users/profile/my-profile-password-change',[ProfileController::class,'myProfilePasswordChange'])->name('myProfilePasswordChange');
    Route::post('users/profile/my-profile-pin-change',[ProfileController::class,'myProfilePinChange'])->name('myProfilePinChange');
    Route::post('users/profile/my-profile-generate-key',[ProfileController::class,'myProfileGenerateKey'])->name('myProfileGenerateKey');
    Route::get('users/profile/my-profile-my-commission',[ProfileController::class,'myProfileCommission'])->name('myProfileCommission');
    Route::post('users/profile/my-profile-my-commission-list',[ProfileController::class,'myProfileCommissionList'])->name('myProfileCommissionList');

    //Mobile Recharge Routes
    Route::get('users/dashboard', [DashboardController::class, 'Dashboard'])->name('dashboardPage');
    Route::get('users/services', [DashboardController::class, 'Services']);
    
    Route::post('users/dashboard/report-data',[DashboardController::class,'dashboardReportsList'])->name('dashboardReportsList');
    //Services Menu Router

    //Mobile Recharge Routes
    Route::get('users/services/mobile',[RechargeController::class,'mobileIndex']);
    Route::post('users/services/provider-state-list',[RechargeController::class,'providerStateList'])->name('servivceProviderStateList');
    Route::post('users/services/recharge',[RechargeController::class,'rechargeCall'])->name('serviceRecharge');
    Route::post('users/services/recharge-report/list',[RechargeController::class,'fetchAll'])->name('serviceRechargeReportsList');
    Route::post('users/services/recharge-reciept',[RechargeController::class,'getRechargeReciept'])->name('serviceRechargeGetReciept');
    Route::post('users/services/recharge-complaint',[RechargeController::class,'submitRechargeComplaint'])->name('serviceRechargeComplaint');
    Route::post('users/services/recharge-roffer',[RechargeController::class,'RechargeCheckRofferFatch'])->name('serviceRechargeCheckRofferFatch');
    Route::post('users/services/recharge-plans',[RechargeController::class,'RechargeCheckPlanFatch'])->name('serviceRechargeCheckPlanFatch');
    Route::post('users/services/recharge-check-mobile',[RechargeController::class,'RechargeCheckMobile'])->name('serviceRechargeCheckMobile');
    //DTH Recharge Routes
    Route::get('users/services/dth',[RechargeController::class,'dthIndex']);
    Route::get('users/services/postpaid',[RechargeController::class,'postpaidIndex']);
    //Bill Payments Routes
    Route::get('users/services/bill-payments', [BillPaymentsController::class, 'index']);
    Route::post('users/services/bill-payments/params', [BillPaymentsController::class, 'fetchProviderParams'])->name('fetchProviderParams');
    Route::post('users/services/bill-payments/fetch', [BillPaymentsController::class, 'fetchBill'])->name('fetchBill');
    Route::post('users/services/bill-payments/pay', [BillPaymentsController::class, 'payBill'])->name('payBill');
    //User List Routes 
    Route::get('users/users/list',[UserListController::class,'index']);
    Route::post('users/users/userlist/list',[UserListController::class,'fetchAll'])->name('userlistList');
    Route::post('users/users/userlist/get',[UserListController::class,'getData'])->name('userlistGet');
    Route::post('users/users/userlist/parent-list',[UserListController::class,'parentListSearchUser'])->name('parentListSearchUser');
    Route::post('users/users/userlist/user-list',[UserListController::class,'userListSearchUser'])->name('userListSearchUser');
    Route::post('users/users/userlist/update',[UserListController::class,'userlistUpdate'])->name('userlistUpdate');
    Route::post('users/users/userlist/fund-transfer',[UserListController::class,'fundUpdate'])->name('fundUpdate');
    //User Fund Request Routes 
    Route::get('users/users/fund-request',[UserFundRequstController::class,'index']);
    Route::post('users/users/fund-request/list',[UserFundRequstController::class,'fetchAll'])->name('userFundRequestList');
    Route::post('users/users/fund-request/search_user',[UserFundRequstController::class,'searchUuser'])->name('userFundRequestSearchUser');
    Route::post('users/users/fund-request/update',[UserFundRequstController::class,'updateData'])->name('userFundRequestUpdate');
    //Bank Routes 
    Route::get('users/users/banks',[BankController::class,'index']);
    Route::post('users/users/banks/list',[BankController::class,'fetchAll'])->name('userBankList');
    Route::post('users/users/banks/delete',[BankController::class,'deleteData'])->name('userBankDelete');
    Route::post('users/users/banks/get',[BankController::class,'getData'])->name('userBankGet');
    Route::post('users/users/banks/update',[BankController::class,'updateData'])->name('userBankUpdate');
    //Fund Request Routes 
    Route::get('users/fund/fund-request',[FundController::class,'index']);
    Route::post('users/fund/fund-request/list',[FundController::class,'fetchAll'])->name('fundRequestList');
    Route::post('users/fund/fund-request/submit',[FundController::class,'fundRequestSubmit'])->name('fundRequestSubmit');
    Route::post('users/fund/fund-request/bank_list',[FundController::class,'fundRequestBankList'])->name('fundRequestBankList');
    //Fund Reports Routes 
    Route::get('users/fund/fund-report',[FundReportsController::class,'index']);
    Route::post('users/fund/fund-report/list',[FundReportsController::class,'fetchAll'])->name('fundReportsList');
    ///Add Money Online
    Route::post('users/fund/add-money/submit',[addMoneyController::class,'addMoneyRequestSubmit'])->name('addMoneyRequestSubmit');

    Route::post('users/fund/add-money-qr-code/submit',[allUpiMoneyController::class,'addMoneyRequestSubmit'])->name('allUpiAddMoneyRequestSubmit');

    
    //Route::post('users/fund/fund-report/search_user',[FundReportsController::class,'searchUuser'])->name('fundsearchUuser');
    ///User Reports
    //Account Reports Routes 
    Route::get('users/user-reports/account-report',[AccountReportsController::class,'index']);
    Route::post('users/user-reports/account-report/list',[AccountReportsController::class,'fetchAll'])->name('accountReportsList');
    //Recharge Reports Routes 
    Route::get('users/user-reports/recharge-report',[RechargeReportsController::class,'index']);
    Route::post('users/user-reports/recharge-report/list',[RechargeReportsController::class,'fetchAll'])->name('rechargeReportsList');
    //Support Routes 
    Route::get('users/support/complaint',[ComplaintController::class,'index']);
    Route::post('users/support/complaint/list',[ComplaintController::class,'fetchAll'])->name('ComplaintsList');
    Route::post('users/support/complaint/get-report',[ComplaintController::class,'getReport'])->name('ComplaintsGetReport');
//Support Routes
Route::get(
    'users/admin-reports/login-history',
    [LoginHistoryController::class, 'index']
)->name('loginHistory');

});




