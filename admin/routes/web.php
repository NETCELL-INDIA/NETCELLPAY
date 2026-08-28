<?php




use Illuminate\Support\Facades\Mail;
use App\Mail\SendEmail;


use Illuminate\Support\Facades\Route;



use App\Http\Controllers\Admin\AuthController;



use App\Http\Controllers\Admin\DashboardController;



use App\Http\Controllers\Admin\SchemeController;
use App\Http\Controllers\Admin\CommissionController;



use App\Http\Controllers\Admin\BankController;



use App\Http\Controllers\Admin\RoleController;



use App\Http\Controllers\Admin\ServiceController;



use App\Http\Controllers\Admin\ProviderController;



use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\SystemSettingController;



use App\Http\Controllers\Admin\SliderController;



use App\Http\Controllers\Admin\ApiController;

use App\Http\Controllers\Admin\PlanCircleDthApiController;



use App\Http\Controllers\Admin\UserListController;
use App\Http\Controllers\Admin\LoginHistoryReportController;



use App\Http\Middleware\AdminCheck;



use App\Http\Controllers\Admin\FundController;



use App\Http\Controllers\Admin\FundReportsController;



use App\Http\Controllers\Admin\AccountReportsController;



use App\Http\Controllers\Admin\RechargeReportsController;
use App\Http\Controllers\Admin\RechargeMenuController;
use App\Http\Controllers\Admin\PendingReportController;
use App\Http\Controllers\Admin\MarginReportController;
use App\Http\Controllers\Admin\CashbackReportController;
use App\Http\Controllers\Admin\ApiReportController;
use App\Http\Controllers\Admin\RefundReportController;
use App\Http\Controllers\Admin\RechargeLogsController;
use App\Http\Controllers\Admin\SendSmsReportController;
use App\Http\Controllers\Admin\SupplierFail2SuccessController;
use App\Http\Controllers\Admin\RehitRechargeHistoryController;
use App\Http\Controllers\Admin\AmountwiseReportController;
use App\Http\Controllers\Admin\ConsumptionReportController;
use App\Http\Controllers\Admin\ROfferReportController;
use App\Http\Controllers\Admin\PlanLogsReportController;
use App\Http\Controllers\Admin\MenuPlaceholderController;



use App\Http\Controllers\Admin\EmailTemplateController;



use App\Http\Controllers\Admin\SmsTemplateController;

use App\Http\Controllers\Admin\SmsApiController;
use App\Http\Controllers\Admin\WhatsappApiController;



use App\Http\Controllers\Admin\AdminReportsController;



use App\Http\Controllers\Admin\ComplaintController;



use App\Http\Controllers\Admin\ProfileController;



use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\WebsiteCmsController;
use App\Http\Controllers\Admin\QueueController;
use App\Http\Controllers\Admin\LogsController;



use App\Http\Controllers\Admin\AmountBlockController;

use App\Http\Controllers\Admin\AmountWizeSwitchController;

use App\Http\Controllers\Admin\StateWizeSwitchController;

use App\Http\Controllers\Admin\UserWizeSwitchController;

use App\Http\Controllers\Admin\RouteSettingController;
use App\Http\Controllers\Admin\GeneralRoutingsController;
use App\Http\Controllers\Admin\OperatorRoutingController;








//use Illuminate\Support\Facades\



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

Route::get('/optimize', function () {
    \Artisan::call('route:clear');
    \Artisan::call('config:clear');
    \Artisan::call('cache:clear');
    \Artisan::call('view:clear');
    \Artisan::call('optimize:clear');

    return 'software optimize';
});

Route::get('admin/optimize', function () {
    \Artisan::call('route:clear');
    \Artisan::call('config:clear');
    \Artisan::call('cache:clear');
    \Artisan::call('view:clear');
    \Artisan::call('optimize:clear');

    return 'software optimize';
});

Route::get('/', function () {



    //return view('welcome');

    

    return Redirect::route('loginPage');



});

Route::get('/clear-login', function () {

    Session::flush();

    return Redirect::route('loginPage');

});





//Admin Portal Routes 



Route::get('admin',[AuthController::class,'Login'])->name('loginPage');
Route::get('admin/login', function () {
    return Redirect::route('loginPage');
});



Route::post('admin/login-check',[AuthController::class,'LoginCheck'])->name('LoginCheck');



Route::post('admin/check-otp-login',[AuthController::class,'checkLoginOtp'])->name('checkLoginOtp');



Route::get('admin/forgot-password',[AuthController::class,'forgotPassword'])->name('forgotPassword');



Route::post('admin/send-otp-forgot-password',[AuthController::class,'sendOtpForgotPassword'])->name('sendOtpForgotPassword');



Route::post('admin/verify-otp-forgot-password',[AuthController::class,'verifyOtpForgotPassword'])->name('verifyOtpForgotPassword');

Route::get('admin/logout',[AuthController::class,'Logout'])->name('adminLogout');

Route::get('admin/slider_image/{filename}', [SliderController::class, 'showImage'])
    ->where('filename', '[^/]+')
    ->name('adminSliderImage');

Route::get('admin/website_media/{filename}', [WebsiteCmsController::class, 'showImage'])
    ->where('filename', '[^/]+')
    ->name('adminWebsiteMedia');
Route::get('website_media/{filename}', [WebsiteCmsController::class, 'showImage'])
    ->where('filename', '[^/]+');



//Admin Portal Routes AdminCheck(middleware)



Route::group(['middleware' => AdminCheck::class], function () {



    Route::get('admin/dashboard', [DashboardController::class, 'Dashboard']);



    Route::post('admin/dashboard/load-wallet',[DashboardController::class,'adminLoadWallet'])->name('adminLoadWallet');



    Route::post('admin/dashboard/report-data',[DashboardController::class,'dashboardReportsList'])->name('dashboardReportsList');

    Route::post('admin/dashboard/top-bar-data',[DashboardController::class,'topbarCount'])->name('topbarCount');

    // Manual Recharge Report (admin-changed status)
    Route::get('admin/recharge-reports/manual-report', [RechargeReportsController::class, 'manualIndex'])->name('manualRechargeReport');

    // Pending Report (full page)
    Route::get('admin/recharge-reports/pending-report', [PendingReportController::class, 'index'])->name('pendingReport');
    Route::post('admin/recharge-reports/pending-report/list', [PendingReportController::class, 'list'])->name('pendingReportList');
    Route::post('admin/recharge-reports/pending-report/bulk-status', [PendingReportController::class, 'bulkStatus'])->name('pendingReportBulkStatus');
    Route::post('admin/recharge-reports/pending-report/rehit', [PendingReportController::class, 'rehit'])->name('pendingReportRehit');
    Route::post('admin/recharge-reports/pending-report/resend', [PendingReportController::class, 'resend'])->name('pendingReportResend');

    // Margin Report (full page)
    Route::get('admin/recharge-reports/margin-report', [MarginReportController::class, 'index'])->name('marginReport');
    Route::post('admin/recharge-reports/margin-report/list', [MarginReportController::class, 'list'])->name('marginReportList');
    Route::post('admin/recharge-reports/margin-report/download', [MarginReportController::class, 'download'])->name('marginReportDownload');

    // Cashback Report (full page)
    Route::get('admin/recharge-reports/cashback-report', [CashbackReportController::class, 'index'])->name('cashbackReport');
    Route::post('admin/recharge-reports/cashback-report/list', [CashbackReportController::class, 'list'])->name('cashbackReportList');
    Route::post('admin/recharge-reports/cashback-report/download', [CashbackReportController::class, 'download'])->name('cashbackReportDownload');

    // API Report
    Route::get('admin/recharge-reports/api-report', [ApiReportController::class, 'index'])->name('apiReport');
    Route::post('admin/recharge-reports/api-report/list', [ApiReportController::class, 'list'])->name('apiReportList');
    Route::post('admin/recharge-reports/api-report/download', [ApiReportController::class, 'download'])->name('apiReportDownload');

    // Refund Report
    Route::get('admin/recharge-reports/refund-report', [RefundReportController::class, 'index'])->name('refundReport');
    Route::post('admin/recharge-reports/refund-report/list', [RefundReportController::class, 'list'])->name('refundReportList');
    Route::post('admin/recharge-reports/refund-report/download', [RefundReportController::class, 'download'])->name('refundReportDownload');

    // Recharge / API Request Logs
    Route::get('admin/recharge-reports/recharge-logs', [RechargeLogsController::class, 'index'])->name('rechargeLogs');
    Route::post('admin/recharge-reports/recharge-logs/list', [RechargeLogsController::class, 'list'])->name('rechargeLogsList');

    // Supplier Fail 2 Success
    Route::get('admin/recharge-reports/supplier-fail-2-success', [SupplierFail2SuccessController::class, 'index'])->name('supplierFail2Success');
    Route::post('admin/recharge-reports/supplier-fail-2-success/list', [SupplierFail2SuccessController::class, 'list'])->name('supplierFail2SuccessList');

    // Rehit Recharge History
    Route::get('admin/recharge-reports/rehit-recharge-history', [RehitRechargeHistoryController::class, 'index'])->name('rehitHistory');
    Route::post('admin/recharge-reports/rehit-recharge-history/list', [RehitRechargeHistoryController::class, 'list'])->name('rehitHistoryList');

    // Amountwise Report
    Route::get('admin/recharge-reports/amountwise-report', [AmountwiseReportController::class, 'index'])->name('amountwiseReport');
    Route::post('admin/recharge-reports/amountwise-report/list', [AmountwiseReportController::class, 'list'])->name('amountwiseReportList');
    Route::post('admin/recharge-reports/amountwise-report/download', [AmountwiseReportController::class, 'download'])->name('amountwiseReportDownload');

    // Consumption Report
    Route::get('admin/recharge-reports/consumption-report', [ConsumptionReportController::class, 'index'])->name('consumptionReport');
    Route::post('admin/recharge-reports/consumption-report/list', [ConsumptionReportController::class, 'list'])->name('consumptionReportList');
    Route::post('admin/recharge-reports/consumption-report/download', [ConsumptionReportController::class, 'download'])->name('consumptionReportDownload');

    // R-Offer Report
    Route::get('admin/recharge-reports/r-offer-report', [ROfferReportController::class, 'index'])->name('rOfferReport');
    Route::post('admin/recharge-reports/r-offer-report/list', [ROfferReportController::class, 'list'])->name('rOfferReportList');
    Route::post('admin/recharge-reports/r-offer-report/download', [ROfferReportController::class, 'download'])->name('rOfferReportDownload');

    // Plan Logs Report
    Route::get('admin/recharge-reports/plan-logs-report', [PlanLogsReportController::class, 'index'])->name('planLogsReport');
    Route::post('admin/recharge-reports/plan-logs-report/list', [PlanLogsReportController::class, 'list'])->name('planLogsReportList');

    // Other sidebar section placeholders (Rambhiya menu structure)
    Route::get('admin/menu/{section}/{slug}', [MenuPlaceholderController::class, 'show'])
        ->whereIn('section', [
            'routings', 'dmt', 'express-dmt', 'users', 'khatabook', 'payments',
            'accounts', 'pancard', 'apis', 'complains', 'operators',
            'w2r', 'employees', 'extras',
        ])
        ->name('adminMenuPlaceholder');

    // Working General Routings
    Route::get('admin/routings/general', [GeneralRoutingsController::class, 'index'])->name('generalRoutings');
    Route::post('admin/routings/general/list', [GeneralRoutingsController::class, 'list'])->name('generalRoutingsList');
    Route::post('admin/routings/general/save', [GeneralRoutingsController::class, 'save'])->name('generalRoutingsSave');
    Route::post('admin/routings/general/delete', [GeneralRoutingsController::class, 'delete'])->name('generalRoutingsDelete');
    Route::post('admin/routings/general/update-field', [GeneralRoutingsController::class, 'updateField'])->name('generalRoutingsUpdateField');
    Route::get('admin/routings/general/search-users', [GeneralRoutingsController::class, 'searchUsers'])->name('generalRoutingsSearchUsers');

    // Operator's Routing (service-wise grid)
    Route::get('admin/routings/operator', [OperatorRoutingController::class, 'index'])->name('operatorRouting');
    Route::get('admin/routings/api-switching', [OperatorRoutingController::class, 'apiSwitching'])->name('apiSwitching');
    Route::post('admin/routings/api-switching/list', [OperatorRoutingController::class, 'apiSwitchingList'])->name('apiSwitchingList');
    Route::post('admin/routings/api-switching/save', [OperatorRoutingController::class, 'apiSwitchingSave'])->name('apiSwitchingSave');
    Route::post('admin/routings/api-switching/delete', [OperatorRoutingController::class, 'apiSwitchingDelete'])->name('apiSwitchingDelete');
    Route::post('admin/routings/operator/list', [OperatorRoutingController::class, 'list'])->name('operatorRoutingList');
    Route::post('admin/routings/operator/save', [OperatorRoutingController::class, 'save'])->name('operatorRoutingSave');
    Route::post('admin/routings/operator/status', [OperatorRoutingController::class, 'updateStatus'])->name('operatorRoutingStatus');
    Route::post('admin/routings/operator/delete', [OperatorRoutingController::class, 'delete'])->name('operatorRoutingDelete');
    Route::post('admin/routings/operator/down-users', [OperatorRoutingController::class, 'downUsers'])->name('operatorRoutingDownUsers');
    Route::post('admin/routings/operator/down-users/search', [OperatorRoutingController::class, 'searchUsers'])->name('operatorRoutingDownUsersSearch');
    Route::post('admin/routings/operator/down-users/add', [OperatorRoutingController::class, 'addDownUser'])->name('operatorRoutingDownUsersAdd');
    Route::post('admin/routings/operator/down-users/remove', [OperatorRoutingController::class, 'removeDownUser'])->name('operatorRoutingDownUsersRemove');

    //My Profile Routes 



    Route::get('admin/profile/my-profile',[ProfileController::class,'myProfile'])->name('myProfile');
    Route::get('admin/profile/change-password',[ProfileController::class,'changePassword'])->name('changePassword');
    Route::get('admin/profile/pin-reset',[ProfileController::class,'pinReset'])->name('pinReset');
    Route::post('admin/profile/pin-reset-change',[ProfileController::class,'pinResetChange'])->name('pinResetChange');
    Route::post('admin/profile/pin-reset-otp-send',[ProfileController::class,'pinResetOtpSend'])->name('pinResetOtpSend');
    Route::post('admin/profile/pin-reset-otp-verify',[ProfileController::class,'pinResetOtpVerify'])->name('pinResetOtpVerify');
    Route::get('admin/profile/login-history',[ProfileController::class,'loginHistory'])->name('loginHistory');
    Route::post('admin/profile/my-profile-data',[ProfileController::class,'myProfileData'])->name('myProfileData');
    Route::post('admin/profile/my-profile-password-change',[ProfileController::class,'myProfilePasswordChange'])->name('myProfilePasswordChange');

    // Queue monitor and API logs
    Route::get('admin/monitor/queue',[QueueController::class,'index'])->name('adminQueueMonitor');
    Route::get('admin/logs/apilogs',[LogsController::class,'apilogs'])->name('adminApilogs');



    //Commission Route

    Route::get('admin/commission',[CommissionController::class,'index'])->name('adminCommission');

    //Scheme Routes 



    Route::get('admin/system/scheme',[SchemeController::class,'index']);



    Route::post('admin/system/scheme/list',[SchemeController::class,'fetchAll'])->name('schemeList');



    Route::post('admin/system/scheme/delete',[SchemeController::class,'deleteData'])->name('schemeDelete');



    Route::post('admin/system/scheme/get',[SchemeController::class,'getData'])->name('schemeGet');



    Route::post('admin/system/scheme/update',[SchemeController::class,'updateData'])->name('schemeUpdate');



    Route::post('admin/system/scheme/commission',[SchemeController::class,'getCommissionData'])->name('schemeGetCommission');



    Route::post('admin/system/scheme/single_set_commission',[SchemeController::class,'SingleUpdateCommission'])->name('schemeSingleUpdateCommission');



    Route::post('admin/system/scheme/bulk_set_commission',[SchemeController::class,'BulkUpdateCommission'])->name('schemeBulkUpdateCommission');



    //Bank Routes 



    Route::get('admin/system/banks',[BankController::class,'index']);



    Route::post('admin/system/banks/list',[BankController::class,'fetchAll'])->name('bankList');



    Route::post('admin/system/banks/delete',[BankController::class,'deleteData'])->name('bankDelete');



    Route::post('admin/system/banks/get',[BankController::class,'getData'])->name('bankGet');



    Route::post('admin/system/banks/update',[BankController::class,'updateData'])->name('bankUpdate');



    //Amount Block



    Route::get('admin/system/amount-block',[AmountBlockController::class,'index']);



    Route::post('admin/system/amount-block/list',[AmountBlockController::class,'fetchAll'])->name('amountBlockList');



    Route::post('admin/system/amount-block/get',[AmountBlockController::class,'getData'])->name('amountBlockGet');



    Route::post('admin/system/amount-block/update',[AmountBlockController::class,'updateData'])->name('amountBlockUpdate');



    Route::post('admin/system/amount-block/delete',[AmountBlockController::class,'deleteData'])->name('amountBlockDelete');

    //Amount Wize Switch

    Route::get('admin/system/amount-wize-switch',[AmountWizeSwitchController::class,'index']);



    Route::post('admin/system/amount-wize-switch/list',[AmountWizeSwitchController::class,'fetchAll'])->name('amountWizeSwitchList');



    Route::post('admin/system/amount-wize-switch/get',[AmountWizeSwitchController::class,'getData'])->name('amountWizeSwitchGet');



    Route::post('admin/system/amount-wize-switch/update',[AmountWizeSwitchController::class,'updateData'])->name('amountWizeSwitchUpdate');



    Route::post('admin/system/amount-wize-switch/delete',[AmountWizeSwitchController::class,'deleteData'])->name('amountWizeSwitchDelete');

    //State Wize Switch

    Route::get('admin/system/state-wize-switch',[StateWizeSwitchController::class,'index']);


    Route::post('admin/system/state-wize-switch/list',[StateWizeSwitchController::class,'fetchAll'])->name('stateWizeSwitchList');


    Route::post('admin/system/state-wize-switch/get',[StateWizeSwitchController::class,'getData'])->name('stateWizeSwitchGet');


    Route::post('admin/system/state-wize-switch/update',[StateWizeSwitchController::class,'updateData'])->name('stateWizeSwitchUpdate');


    Route::post('admin/system/state-wize-switch/delete',[StateWizeSwitchController::class,'deleteData'])->name('stateWizeSwitchDelete');


    //User Wize Switch

    Route::get('admin/system/user-wize-switch',[UserWizeSwitchController::class,'index']);

    Route::post('admin/system/user-wize-switch/list',[UserWizeSwitchController::class,'fetchAll'])->name('userWizeSwitchList');

    Route::post('admin/system/user-wize-switch/get',[UserWizeSwitchController::class,'getData'])->name('userWizeSwitchGet');

    Route::post('admin/system/user-wize-switch/update',[UserWizeSwitchController::class,'updateData'])->name('userWizeSwitchUpdate');

    Route::post('admin/system/user-wize-switch/delete',[UserWizeSwitchController::class,'deleteData'])->name('userWizeSwitchDelete');
    //Role Routes 



    Route::get('admin/system/role',[RoleController::class,'index']);



    Route::post('admin/system/role/list',[RoleController::class,'fetchAll'])->name('roleList');



    



    //Service Routes 



    Route::get('admin/system/services',[ServiceController::class,'index']);



    Route::post('admin/system/services/list',[ServiceController::class,'fetchAll'])->name('servicesList');



    //Provider Routes 



    Route::get('admin/system/providers',[ProviderController::class,'index']);



    Route::post('admin/system/providers/list',[ProviderController::class,'fetchAll'])->name('providersList');



    Route::post('admin/system/providers/delete',[ProviderController::class,'deleteData'])->name('providersDelete');



    Route::post('admin/system/providers/get',[ProviderController::class,'getData'])->name('providersGet');



    Route::post('admin/system/providers/api_and_service',[ProviderController::class,'getDataService'])->name('apiAndService');



    Route::post('admin/system/providers/update',[ProviderController::class,'updateData'])->name('providersUpdate');



    //Announcement Routes 



    Route::get('admin/system/announcement',[AnnouncementController::class,'index']);



    Route::post('admin/system/announcement/update',[AnnouncementController::class,'updateData'])->name('announcementUpdate');



    Route::post('admin/system/announcement/get',[AnnouncementController::class,'getData'])->name('announcementGet');

    Route::post('admin/system-settings/save', [SystemSettingController::class, 'save'])->name('systemSettingSave');
    Route::get('admin/system-settings/{page?}', [SystemSettingController::class, 'show'])->name('systemSettingPage');



    //Slider Routes 



    Route::get('admin/system/slider',[SliderController::class,'index']);



    Route::post('admin/system/slider/list',[SliderController::class,'fetchAll'])->name('sliderList');



    Route::post('admin/system/slider/delete',[SliderController::class,'deleteData'])->name('sliderDelete');



    Route::post('admin/system/slider/get',[SliderController::class,'getData'])->name('sliderGet');



    Route::post('admin/system/slider/update',[SliderController::class,'updateData'])->name('sliderUpdate');



    //Api Routes 



    Route::get('admin/system/apis',[ApiController::class,'index']);

    Route::get('admin/system/plan-circle-dth-api', [PlanCircleDthApiController::class, 'index']);
    Route::get('admin/apis/plan-circle-dth-api', [PlanCircleDthApiController::class, 'index']);
    Route::get('admin/apis/plan_circle_fetch_api_settings', [PlanCircleDthApiController::class, 'index']);
    Route::post('admin/system/plan-circle-dth-api/list', [PlanCircleDthApiController::class, 'list'])->name('planCircleDthApiList');
    Route::post('admin/system/plan-circle-dth-api/save', [PlanCircleDthApiController::class, 'save'])->name('planCircleDthApiSave');
    Route::post('admin/system/plan-circle-dth-api/reset', [PlanCircleDthApiController::class, 'reset'])->name('planCircleDthApiReset');

    Route::post('admin/system/apis/list',[ApiController::class,'fetchAll'])->name('apisList');



    Route::post('admin/system/apis/delete',[ApiController::class,'deleteData'])->name('apisDelete');



    Route::post('admin/system/apis/get',[ApiController::class,'getData'])->name('apisGet');



    Route::post('admin/system/apis/update',[ApiController::class,'updateData'])->name('apisUpdate');



    Route::post('admin/system/apis/check-live-balance',[ApiController::class,'CheckLiveBalance'])->name('apisCheckLiveBalance');

    Route::post('admin/system/apis/request-logs',[ApiController::class,'apiRequestLogs'])->name('apisRequestLogs');



    Route::post('admin/system/apis/provider-code',[ApiController::class,'getProviderCodeData'])->name('apisGetProviderCode');



    Route::post('admin/system/apis/single-set-provider-code',[ApiController::class,'SingleUpdateProviderCode'])->name('apisSingleUpdateProviderCode');



    Route::post('admin/system/apis/bulk-set-provider-code',[ApiController::class,'BulkUpdateProviderCode'])->name('apisBulkUpdateProviderCode');



    Route::post('admin/system/apis/state-code',[ApiController::class,'getStateCodeData'])->name('apisGetStateCode');

    Route::post('admin/system/apis/single-set-state-code',[ApiController::class,'SingleUpdateStateCode'])->name('apisSingleUpdateStateCode');


    Route::post('admin/system/apis/bulk-set-state-code',[ApiController::class,'BulkUpdateStateCode'])->name('apisBulkUpdateStateCode');



    //User List Routes 



    Route::get('admin/users/list',[UserListController::class,'index']);
    Route::get('admin/users/login-history', [LoginHistoryReportController::class, 'index'])->name('usersLoginHistory');



    Route::post('admin/users/userlist/list',[UserListController::class,'fetchAll'])->name('userlistList');



    Route::post('admin/users/userlist/parent-list',[UserListController::class,'parentListSearchUuser'])->name('parentListSearchUuser');



    Route::post('admin/users/userlist/delete',[UserListController::class,'deleteData'])->name('userlistDelete');



    Route::post('admin/users/userlist/get',[UserListController::class,'getData'])->name('userlistGet');



    Route::post('admin/users/userlist/update',[UserListController::class,'updateData'])->name('userlistUpdate');



    Route::post('admin/users/userlist/fundupdate',[UserListController::class,'fundUpdate'])->name('fundUpdate');



    Route::post('admin/users/userlist/resetpassword',[UserListController::class,'resetPassword'])->name('resetPassword');



    Route::post('admin/users/userlist/resetPIN',[UserListController::class,'resetPIN'])->name('resetPIN');
    Route::post('admin/users/userlist/lookup-pincode',[UserListController::class,'lookupPincode'])->name('userlistLookupPincode');



    //Send Message Routes 



    Route::get('admin/users/send-message',[UserListController::class,'sendMessage']);



    Route::post('admin/users/send-message-users',[UserListController::class,'sendMessageUsers'])->name('sendMessageUsers');

    Route::get('admin/extras/send-sms-report', [SendSmsReportController::class, 'index'])->name('sendSmsReport');
    Route::post('admin/extras/send-sms-report/list', [SendSmsReportController::class, 'list'])->name('sendSmsReportList');



    //Fund Request Routes 



    Route::get('admin/fund/fund-request',[FundController::class,'index']);



    Route::post('admin/fund/fund-request/list',[FundController::class,'fetchAll'])->name('fundRequestList');



    Route::post('admin/fund/fund-request/search_user',[FundController::class,'searchUuser'])->name('fundRequestSearchUser');



    Route::post('admin/fund/fund-request/update',[FundController::class,'updateData'])->name('fundRequestUpdate');



    //Fund Reports Routes 



    Route::get('admin/fund/fund-report',[FundReportsController::class,'index']);



    Route::post('admin/fund/fund-report/list',[FundReportsController::class,'fetchAll'])->name('fundReportsList');

    Route::post('admin/fund/fund-report/export',[FundReportsController::class,'fetchAllExport'])->name('fundReportsListExport');



    Route::post('admin/fund/fund-report/search_user',[FundReportsController::class,'searchUuser'])->name('fundsearchUuser');



    //Account Reports Routes 



    Route::get('admin/user-reports/account-report',[AccountReportsController::class,'index']);



    Route::post('admin/user-reports/account-report/list',[AccountReportsController::class,'fetchAll'])->name('accountReportsList');

    Route::post('admin/user-reports/account-report/export',[AccountReportsController::class,'fetchAllExport'])->name('accountReportsExport');



    Route::post('admin/user-reports/account-report/search_user',[AccountReportsController::class,'searchUuser'])->name('accountsearchUuser');



    //Recharge Reports Routes 



    Route::get('admin/user-reports/recharge-report',[RechargeReportsController::class,'index']);
    Route::post('admin/user-reports/recharge-report/list',[RechargeReportsController::class,'fetchAll'])->name('rechargeReportsList');
    Route::post('admin/user-reports/recharge-report/list-modern',[RechargeReportsController::class,'fetchAllModern'])->name('rechargeReportsListModern');
    Route::post('admin/user-reports/recharge-report/download-modern',[RechargeReportsController::class,'downloadModern'])->name('rechargeReportsDownloadModern');
  	Route::post('admin/user-reports/recharge-report/export',[RechargeReportsController::class,'fetchAllExport'])->name('rechargeReportsListExport');



    Route::post('admin/user-reports/recharge-report/search_user',[RechargeReportsController::class,'searchUuser'])->name('rechargeSearchUuser');



    Route::post('admin/user-reports/recharge-report/get-provider',[RechargeReportsController::class,'getProvider'])->name('rechargeGetProvider');



    Route::post('admin/user-reports/recharge-report/get-apis',[RechargeReportsController::class,'getApis'])->name('rechargeGetAPis');



    Route::post('admin/user-reports/recharge-report/change-operator-id',[RechargeReportsController::class,'changeOperatorId'])->name('changeOperatorId');



    Route::post('admin/user-reports/recharge-report/get-complaint',[RechargeReportsController::class,'getComplaint'])->name('getComplaint');



    Route::post('admin/user-reports/recharge-report/update-complaint',[RechargeReportsController::class,'updateComplaint'])->name('updateComplaint');



    Route::post('admin/user-reports/recharge-report/update-status',[RechargeReportsController::class,'updateStatus'])->name('updateStatus');



    Route::post('admin/user-reports/recharge-report/check-api-logs',[RechargeReportsController::class,'checkApiLog'])->name('checkApiLog');



    //Manage Company Routes 



    Route::get('admin/company/manage-company',[CompanyController::class,'index']);



    Route::post('admin/company/manage-company/list',[CompanyController::class,'fetchAll'])->name('manageCompanyList');



    Route::post('admin/company/manage-company/get',[CompanyController::class,'getData'])->name('manageCompanyGet');



    Route::post('admin/company/manage-company/update',[CompanyController::class,'updateData'])->name('manageCompanyUpdate');

    Route::get('admin/website/ads', [WebsiteCmsController::class, 'ads']);
    Route::get('admin/website/pages', [WebsiteCmsController::class, 'pages']);
    Route::post('admin/website/pages/get', [WebsiteCmsController::class, 'pageGet'])->name('websitePageGet');
    Route::post('admin/website/pages/save', [WebsiteCmsController::class, 'pageSave'])->name('websitePageSave');
    Route::get('admin/website/setting', [WebsiteCmsController::class, 'setting']);
    Route::post('admin/website/setting/save', [WebsiteCmsController::class, 'saveSetting'])->name('websiteSettingSave');
    Route::get('admin/website/policy', [WebsiteCmsController::class, 'policy']);
    Route::post('admin/website/policy/save', [WebsiteCmsController::class, 'savePolicy'])->name('websitePolicySave');
    Route::get('admin/website/banners', [WebsiteCmsController::class, 'banners']);
    Route::get('admin/website/popups', [WebsiteCmsController::class, 'popups']);
    Route::post('admin/website/media/list', [WebsiteCmsController::class, 'mediaList'])->name('websiteMediaList');
    Route::post('admin/website/media/get', [WebsiteCmsController::class, 'mediaGet'])->name('websiteMediaGet');
    Route::post('admin/website/media/save', [WebsiteCmsController::class, 'mediaSave'])->name('websiteMediaSave');
    Route::post('admin/website/media/delete', [WebsiteCmsController::class, 'mediaDelete'])->name('websiteMediaDelete');



    //Email Template Routes 



    Route::get('admin/company/email-template',[EmailTemplateController::class,'index']);



    Route::post('admin/company/email-template/list',[EmailTemplateController::class,'fetchAll'])->name('emailTemplateList');



    Route::post('admin/company/email-template/delete',[EmailTemplateController::class,'deleteData'])->name('emailTemplateDelete');



    Route::post('admin/company/email-template/get',[EmailTemplateController::class,'getData'])->name('emailTemplateGet');



    Route::post('admin/company/email-template/update',[EmailTemplateController::class,'updateData'])->name('emailTemplateUpdate');



    //SMS Template Routes 



    Route::get('admin/company/sms-template',[SmsTemplateController::class,'index']);



    Route::post('admin/company/sms-template/list',[SmsTemplateController::class,'fetchAll'])->name('smsTemplateList');



    Route::post('admin/company/sms-template/delete',[SmsTemplateController::class,'deleteData'])->name('smsTemplateDelete');



    Route::post('admin/company/sms-template/get',[SmsTemplateController::class,'getData'])->name('smsTemplateGet');



    Route::post('admin/company/sms-template/update',[SmsTemplateController::class,'updateData'])->name('smsTemplateUpdate');

    // SMS API List
    Route::get('admin/extras/sms-api-list', [SmsApiController::class, 'index'])->name('smsApiListPage');
    Route::get('admin/company/sms-api-list', [SmsApiController::class, 'index']);
    Route::post('admin/extras/sms-api-list/list', [SmsApiController::class, 'fetchAll'])->name('smsApiList');
    Route::post('admin/extras/sms-api-list/get', [SmsApiController::class, 'getData'])->name('smsApiGet');
    Route::post('admin/extras/sms-api-list/update', [SmsApiController::class, 'updateData'])->name('smsApiUpdate');
    Route::post('admin/extras/sms-api-list/delete', [SmsApiController::class, 'deleteData'])->name('smsApiDelete');
    Route::post('admin/extras/sms-api-list/set-primary', [SmsApiController::class, 'setPrimary'])->name('smsApiSetPrimary');

    Route::get('admin/extras/whatsapp-api', [WhatsappApiController::class, 'index'])->name('whatsappApiPage');
    Route::post('admin/extras/whatsapp-api/save', [WhatsappApiController::class, 'save'])->name('whatsappApiSave');
    Route::post('admin/extras/whatsapp-api/test', [WhatsappApiController::class, 'test'])->name('whatsappApiTest');

    ///Routes Settings

    Route::get('admin/company/routes-settings',[RouteSettingController::class,'index']);


    Route::post('admin/company/routes-settings/list',[RouteSettingController::class,'fetchAll'])->name('routeSettingsList');

    Route::post('admin/company/routes-settings/update-priority',[RouteSettingController::class,'routesBulkUpdatePriority'])->name('routesBulkUpdatePriority');



    //Live Recharge Reports Routes



    Route::get('admin/admin-reports/recharge-live-reports',[AdminReportsController::class,'liveRechargeReports']);
    Route::post('admin/admin-reports/recharge-live-reports/list',[AdminReportsController::class,'liveRechargeReportsList'])->name('liveRechargeReportsList');
    Route::post('admin/admin-reports/recharge-live-reports/download',[AdminReportsController::class,'liveRechargeReportsDownload'])->name('liveRechargeReportsDownload');



    //User Sale Reports Routes



    Route::get('admin/admin-reports/user-sale-report',[AdminReportsController::class,'userSaleReports']);


    Route::post('admin/admin-reports/user-sale-report/list',[AdminReportsController::class,'userSaleReportsList'])->name('userSaleReportsList');


    //Md And Dt Sale Reports Routes
    Route::get('admin/admin-reports/md-dt-sale-report',[AdminReportsController::class,'mdAndDtSaleReports']);

    Route::post('admin/admin-reports/md-dt-sale-report/list',[AdminReportsController::class,'mdAndDtSaleReportsList'])->name('mdAndDtSaleReportsList');



    //Provider Sale Reports Routes



    Route::get('admin/admin-reports/provider-sale-report',[AdminReportsController::class,'providerSaleReports']);



    Route::post('admin/admin-reports/provider-sale-report/list',[AdminReportsController::class,'providerSaleReportsList'])->name('providerSaleReportsList');



    //Provider Sale Reports Routes



    Route::get('admin/admin-reports/api-sale-report',[AdminReportsController::class,'apiSaleReports']);



    Route::post('admin/admin-reports/api-list',[AdminReportsController::class,'apiList'])->name('apiList');



    Route::post('admin/admin-reports/api-sale-report/list',[AdminReportsController::class,'apiSaleReportsList'])->name('apiSaleReportsList');



    //Api Log Reports Routes



    Route::get('admin/admin-reports/api-log-report',[AdminReportsController::class,'apiLogReports']);



    Route::post('admin/admin-reports/api-log-report/list',[AdminReportsController::class,'apiLogReportsList'])->name('apiLogReportsList');



    //Complaints Routes



    Route::get('admin/support/complaint',[ComplaintController::class,'index']);



    Route::post('admin/support/complaint/list',[ComplaintController::class,'fetchAll'])->name('ComplaintsList');



    Route::post('admin/support/complaint/get-report',[ComplaintController::class,'getReport'])->name('ComplaintsGetReport');

    Route::post('admin/support/complaint/close',[ComplaintController::class,'closeComplaint'])->name('ComplaintsClose');

    Route::post('admin/support/complaint/clear-success',[ComplaintController::class,'clearSuccessTickets'])->name('ComplaintsClearSuccess');







});































