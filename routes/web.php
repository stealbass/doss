<?php

use App\Http\Controllers\AamarpayController;
use App\Http\Controllers\AuthorizeNetController;
use App\Http\Controllers\CaseTypeController;
use App\Http\Controllers\CinetPayController;
use App\Http\Controllers\FedapayController;
use App\Http\Controllers\KhaltiPaymentController;
use App\Http\Controllers\LegalLibraryController;
use App\Http\Controllers\NepalstePaymnetController;
use App\Http\Controllers\OzowController;
use App\Http\Controllers\UserLegalLibraryController;
use App\Http\Controllers\PaiementProController;
use App\Http\Controllers\TapPaymentController;
use App\Http\Controllers\MobileAppSettingsController;
use App\Http\Controllers\MobileUsersController;
use App\Http\Controllers\MobileAppPlansController;
use App\Http\Controllers\MobileAnalyticsController;
use App\Http\Controllers\MobileDashboardController;
use App\Http\Controllers\MobileAppSubscriptionsController;
use App\Http\Controllers\OpenAIDiagnosticsController;
use App\Http\Controllers\PushNotificationsController;
use App\Http\Controllers\MobileLegalLibraryController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\FiscalSocialResourceController;
use App\Http\Controllers\CalculatorController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdvocateController;
use App\Http\Controllers\BenchController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\CaseNoteController;
use App\Http\Controllers\CauseController;
use App\Http\Controllers\CountryStateCityController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\HighCourtController;
use App\Http\Controllers\ToDoController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\DiaryController;
use App\Http\Controllers\TimeSheetController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DoctypeController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\PaypalController;
use App\Http\Controllers\PaystackPaymentController;
use App\Http\Controllers\FlutterwavePaymentController;
use App\Http\Controllers\RazorpayPaymentController;
use App\Http\Controllers\MercadoPaymentController;
use App\Http\Controllers\PaytmPaymentController;
use App\Http\Controllers\MolliePaymentController;
use App\Http\Controllers\SkrillPaymentController;
use App\Http\Controllers\CoingatePaymentController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PlanRequestController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\BankTransferController;
use App\Http\Controllers\BenefitPaymentController;
use App\Http\Controllers\CashfreeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ConversionController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\DealStageController;
use App\Http\Controllers\HearingController;
use App\Http\Controllers\HearingTypeController;
use App\Http\Controllers\IyziPayController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\PayfastController;
use App\Http\Controllers\PaymentWallController;
use App\Http\Controllers\PaytabController;
use App\Http\Controllers\PaytrController;
use App\Http\Controllers\SspayController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\ToyyibpayController;
use App\Http\Controllers\UserlogController;
use App\Http\Controllers\XenditPaymentController;
use App\Http\Controllers\YooKassaController;
use App\Http\Controllers\DocSubTypeController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KnowledgebaseCategoryController;
use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadStageController;
use App\Http\Controllers\MotionController;
use App\Http\Controllers\OperatinghoursController;
use App\Http\Controllers\PayHereController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\PriorityController;
use App\Http\Controllers\SLAPoliciyController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketCustomFieldController;
use App\Http\Controllers\AiTemplateController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\GoogleAuthenticationController;
use App\Http\Controllers\PayUPaymentController;
use App\Http\Controllers\PowerTranzController;


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


require __DIR__ . '/auth.php';

Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    Artisan::call('optimize:clear');

    return redirect()->back()->with('success', __('Clear Cache successfully.'));
});

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::group(['middleware' => ['auth', 'XSS', 'verified']], function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('permissions', PermissionController::class);
    Route::resource('roles', RoleController::class);

    Route::resource('bills', BillController::class);
    Route::get('bills/addpayment/{bill_id}', [BillController::class, 'paymentcreate'])->name('create.payment');
    Route::POST('bills/storepayment/{bill_id}', [BillController::class, 'paymentstore'])->name('payment.store');
    Route::get('bill/export', [BillController::class, 'exportFile'])->name('bills.export');
    Route::get('bill/{id}/send-email', [BillController::class, 'sendEmail'])->name('bill.send.email');
    Route::post('bill/{id}/send-email', [BillController::class, 'postSendEmail'])->name('bill.post.send.email');
    Route::get('payment/{id}/receipt', [BillController::class, 'showPaymentReceipt'])->name('payment.receipt');
    Route::get('bank-payment/{id}/receipt', [BillController::class, 'showBankPaymentReceipt'])->name('bank.payment.receipt');

    Route::resource('users', UserController::class);
    Route::get('users-list', [UserController::class, 'userList'])->name('users.list');
    Route::post('users/{id}/change-password', [UserController::class, 'changeMemberPassword'])->name('member.change.password');
    Route::get('user/{id}/plan', [UserController::class, 'upgradePlan'])->name('plan.upgrade');
    Route::get('user/{id}/plan/{pid}', [UserController::class, 'activePlan'])->name('plan.active');
    Route::get('user/{id}/plans/{planid}', [UserController::class, 'deactivatePlan'])->name('plan.deactivate');
    Route::get('users/{id}/documents/download-all', [UserController::class, 'downloadAllDocuments'])->name('users.documents.download');
    Route::delete('users/{id}/documents', [UserController::class, 'deleteAllDocuments'])->name('users.documents.delete');
    Route::any('company-reset-password/{id}', [UserController::class, 'companyPassword'])->name('company.reset');
    Route::any('users/verify/{id}', [UserController::class, 'verify'])->name('users.verify');
    Route::get('users/detail/{id}', [UserController::class, 'detail'])->name('users.detail');
    Route::get('home/{id}/login-with-admin', [UserController::class, 'LoginWithAdmin'])->name('login.with.admin');
    Route::get('login-with-admin/exit', [UserController::class, 'ExitAdmin'])->name('exit.admin');
    Route::get('company-info/{id}', [UserController::class, 'companyInfo'])->name('company.info');
    Route::post('user-unable', [UserController::class, 'userUnable'])->name('user.unable');
    Route::get('user-login/{id}', [UserController::class, 'LoginManage'])->name('users.login');
    Route::any('user-reset-password/{id}', [UserController::class, 'userPassword'])->name('users.reset');
    Route::post('user-reset-password/{id}', [UserController::class, 'userPasswordReset'])->name('user.password.update');

    Route::resource('userlog', UserlogController::class)->except(['destroy']);
    Route::delete('/userlog/{id}/', [UserlogController::class, 'destroy'])->name('userlog.destroy');
    Route::get('userlog-view/{id}/', [UserlogController::class, 'view'])->name('userlog.view');

    Route::resource('employee', EmployeeController::class);

    Route::resource('groups', GroupController::class);

    Route::resource('client', ClientController::class);
    Route::get('client-list', [ClientController::class, 'userList'])->name('client.list');
    Route::get('/client-fileimport', [ClientController::class, 'fileImport'])->name('clients.file.import');
    Route::post('import/client', [ClientController::class, 'import'])->name('client.import');
    Route::get('clients/export', [ClientController::class, 'exportFile'])->name('clients.export');

    Route::resource('advocate', AdvocateController::class);
    Route::get('/advocate/contacts/{id}', [AdvocateController::class, 'contacts'])->name('advocate.contacts');
    Route::get('/advocate/bills/{id}', [AdvocateController::class, 'bills'])->name('advocate.bill');
    Route::get('advocates/export', [AdvocateController::class, 'exportFile'])->name('advocates.export');
    Route::get('/advocates-fileimport', [AdvocateController::class, 'fileImport'])->name('advocates.file.import');
    Route::post('import/advocates', [AdvocateController::class, 'import'])->name('advocates.import');
    Route::get('/advocate/view/{id}', [AdvocateController::class, 'view'])->name('advocate.view');

    Route::resource('cases', CaseController::class);
    Route::get('cases/journey/{id}', [CaseController::class, 'journey'])->name('cases.journey');
    Route::post('cases/journey-update/{id}', [CaseController::class, 'updateJourney'])->name('update.journey');
    Route::get('/cases/docs-delete/{id}/{key}', [CaseController::class, 'casesDocsDestroy'])->name('cases.docs.destroy');
    Route::get('import/case/file', [CaseController::class, 'importFile'])->name('case.file.import');
    Route::post('import/case', [CaseController::class, 'import'])->name('case.import');
    Route::get('case/export', [CaseController::class, 'exportFile'])->name('cases.export');

    Route::get('/hearing/{case_id}', [HearingController::class, 'create'])->name('hearings.create');
    Route::resource('hearing', HearingController::class);
    Route::get('import/hearing/file/{case_id}', [HearingController::class, 'importFile'])->name('hearing.file.import');
    Route::post('import/hearing', [HearingController::class, 'import'])->name('hearing.import');

    Route::resource('to-do', ToDoController::class);
    Route::get('to-do/status/{id}', [ToDoController::class, 'status'])->name('to-do.status');
    Route::PUT('to-do/status-update/{id}', [ToDoController::class, 'statusUpdate'])->name('to-do.status.update');
    Route::post('/get-advocate', [ToDoController::class, 'assgineadvocate'])->name('get.advocate');
    Route::post('/get-client', [ToDoController::class, 'assgineclient'])->name('get.client');

    Route::resource('casediary', DiaryController::class);

    Route::resource('calendar', CalendarController::class);
    Route::post('data/get_all_data', [CalendarController::class, 'get_call_data'])->name('call.get_call_data');

    Route::resource('documents', DocumentController::class);

    // Case Notes Routes
    Route::get('case-notes/create/{case_id}', [CaseNoteController::class, 'create'])->name('case-notes.create');
    Route::post('case-notes/store', [CaseNoteController::class, 'store'])->name('case-notes.store');
    Route::get('case-notes/reply-form/{note_id}', [CaseNoteController::class, 'replyForm'])->name('case-notes.reply-form');
    Route::post('case-notes/reply', [CaseNoteController::class, 'reply'])->name('case-notes.reply');
    Route::delete('case-notes/{id}', [CaseNoteController::class, 'destroy'])->name('case-notes.destroy');

    // Legal Library Routes - User Access (All authenticated users)
    Route::prefix('library')->name('user.legal-library.')->group(function () {
        Route::get('/', [UserLegalLibraryController::class, 'index'])->name('index');
        Route::get('/category/{categoryId}', [UserLegalLibraryController::class, 'showCategory'])->name('category');
        Route::get('/document/{id}/view', [UserLegalLibraryController::class, 'viewDocument'])->name('view');
        Route::get('/document/{id}/stream', [UserLegalLibraryController::class, 'streamDocument'])->name('stream');
        Route::get('/document/{id}/download', [UserLegalLibraryController::class, 'downloadDocument'])->name('download');
    });

    Route::resource('cause', CauseController::class);
    Route::post('/cause/get-highcourts', [CauseController::class, 'getHighCourt'])->name('get.highcourt');
    Route::post('/cause/get-bench', [CauseController::class, 'getBench'])->name('get.bench');

    Route::resource('timesheet', TimeSheetController::class);
    Route::get('timesheets/export', [TimeSheetController::class, 'exportFile'])->name('timesheets.export');

    Route::resource('expenses', ExpenseController::class);
    Route::get('expense/export', [ExpenseController::class, 'exportFile'])->name('expenses.export');

    Route::resource('fee-receive', FeeController::class);
    Route::get('feereceive/export', [FeeController::class, 'exportFile'])->name('feereceive.export');

    Route::resource('plans', PlanController::class);
    Route::get('plan/plan-trial/{id}', [PlanController::class, 'PlanTrial'])->name('plan.trial');
    Route::get('/payment/{code}', [PlanController::class, 'payment'])->name('payment');
    Route::post('update-plan-status', [PlanController::class, 'updateStatus'])->name('update.plan.status');
    Route::get('plan-refund/{id}/{orderId}', [PlanController::class, 'Refund'])->name('plan.refund');

    // Legal Library Routes - Super Admin Management (Global Library)
    Route::prefix('legal-library')->name('legal-library.')->group(function () {
        Route::get('/', [LegalLibraryController::class, 'index'])->name('index');
        
        // Category routes
        Route::get('/category/create', [LegalLibraryController::class, 'createCategory'])->name('category.create');
        Route::post('/category/store', [LegalLibraryController::class, 'storeCategory'])->name('category.store');
        Route::get('/category/{id}/edit', [LegalLibraryController::class, 'editCategory'])->name('category.edit');
        Route::put('/category/{id}', [LegalLibraryController::class, 'updateCategory'])->name('category.update');
        Route::delete('/category/{id}', [LegalLibraryController::class, 'destroyCategory'])->name('category.destroy');
        
        // Bulk assign countries
        Route::get('/bulk-assign-countries', [LegalLibraryController::class, 'bulkAssignCountries'])->name('bulk-assign-countries');
        Route::post('/bulk-assign-countries', [LegalLibraryController::class, 'saveBulkAssignCountries'])->name('bulk-assign-countries');
        
        // Document routes
        Route::get('/category/{categoryId}/documents', [LegalLibraryController::class, 'showDocuments'])->name('documents');
        Route::get('/category/{categoryId}/document/create', [LegalLibraryController::class, 'createDocument'])->name('document.create');
        Route::post('/category/{categoryId}/document/store', [LegalLibraryController::class, 'storeDocument'])->name('document.store');
        
        // Bulk upload routes
        Route::get('/category/{categoryId}/bulk-upload', [LegalLibraryController::class, 'bulkUploadForm'])->name('bulk-upload.form');
        Route::post('/category/{categoryId}/bulk-upload', [LegalLibraryController::class, 'bulkUploadStore'])->name('bulk-upload.store');
        
        Route::get('/document/{id}/edit', [LegalLibraryController::class, 'editDocument'])->name('document.edit');
        Route::put('/document/{id}', [LegalLibraryController::class, 'updateDocument'])->name('document.update');
        Route::delete('/document/{id}', [LegalLibraryController::class, 'destroyDocument'])->name('document.destroy');
        Route::get('/document/{id}/download', [UserLegalLibraryController::class, 'downloadDocument'])->name('document.download');
    });

    Route::get('plan_request', [PlanRequestController::class, 'index'])->name('plan_request.index');
    Route::get('request_send/{id}', [PlanRequestController::class, 'userRequest'])->name('send.request');
    Route::get('request_cancel/{id}', [PlanRequestController::class, 'cancelRequest'])->name('request.cancel');
    Route::get('request_response/{id}/{response}', [PlanRequestController::class, 'acceptRequest'])->name('response.request');

    Route::resource('referral', ReferralController::class);
    Route::post('referral-settings', [ReferralController::class, 'savereferralSettings'])->name('referral.settings');

    Route::resource('payout', PayoutController::class)->except(['update']);
    Route::get('/payout/update/{id}/{status}', [PayoutController::class, 'update'])->name('payout.update');

    Route::resource('courts', CourtController::class);

    Route::resource('highcourts', HighCourtController::class);

    Route::resource('bench', BenchController::class);

    Route::resource('taxs', TaxController::class);
    Route::post('taxs/get-tax/', [TaxController::class, 'getTax'])->name('get.tax');

    Route::resource('casetype', CaseTypeController::class);

    Route::resource('doctype', DoctypeController::class);

    Route::resource('doctsubype', DocSubTypeController::class);
    Route::post('doctsubype/getDocSubType', [DocSubTypeController::class, 'getDocSubType'])->name('get.docSubType');

    Route::resource('hearingType', HearingTypeController::class);

    Route::resource('motions', MotionController::class);

    Route::resource('pipeline', PipelineController::class);

    Route::post('leadStage/order', [LeadStageController::class, 'order'])->name('leadStage.order');
    Route::post('leadStage/json', [LeadStageController::class, 'json'])->name('leadStage.json');
    Route::resource('leadStage', LeadStageController::class);

    Route::post('dealStage/order', [DealStageController::class, 'order'])->name('dealStage.order');
    Route::post('dealStage/json', [DealStageController::class, 'json'])->name('dealStage.json');
    Route::resource('dealStage', DealStageController::class);

    Route::resource('source', SourceController::class);

    Route::resource('label', LabelController::class);

    Route::resource('coupons', CouponController::class);
    Route::get('/apply-coupon', [CouponController::class, 'applyCoupon'])->name('apply.coupon');

    Route::get('/orders', [StripePaymentController::class, 'index'])->name('order.index');

    Route::resource('settings', SettingController::class);
    Route::post('storage-settings', [SettingController::class, 'storageSettingStore'])->name('storage.setting.store');
    Route::post('cookie-setting', [SettingController::class, 'saveCookieSettings'])->name('cookie.setting');
    Route::post('email-settings', [SettingController::class, 'saveCompanyEmailSettings'])->name('email.settings');
    Route::post('company-email-settings', [SettingController::class, 'saveCompanyEmailSettings'])->name('company.email.settings');
    Route::any('test', [SettingController::class, 'testMail'])->name('test.mail');
    Route::post('test-mail', [SettingController::class, 'testSendMail'])->name('test.send.mail');
    Route::post('setting/seo', [SettingController::class, 'SeoSettings'])->name('seo.settings');
    Route::post('payment-setting', [SettingController::class, 'savePaymentSettings'])->name('payment.settings');
    Route::post('admin-payment-setting', [SettingController::class, 'saveAdminPaymentSettings'])->name('admin.payment.settings');
    Route::post('recaptcha-settings', [SettingController::class, 'recaptchaSettingStore'])->name('recaptcha.settings.store');
    Route::get('system-settings', [SettingController::class, 'adminSettings'])->name('admin.settings');
    Route::post('business-setting', [SettingController::class, 'saveBusinessSettings'])->name('business.setting');
    Route::post('pusher-setting', [SettingController::class, 'savePusherSettings'])->name('pusher.setting');
    Route::post('setting/google-calender', [SettingController::class, 'saveGoogleCalenderSettings'])->name('google.calender.settings');
    Route::post('chatgptkey', [SettingController::class, 'chatgptkey'])->name('settings.chatgptkey');

    // Mobile App Dashboard
    Route::get('mobile-dashboard', [MobileDashboardController::class, 'index'])->name('mobile.dashboard');

    // Mobile App Settings Routes
    Route::get('mobile-app-settings', [MobileAppSettingsController::class, 'index'])->name('mobile-app-settings.index');
    Route::post('mobile-app-settings/update-version', [MobileAppSettingsController::class, 'updateVersion'])->name('mobile-app-settings.update-version');
    Route::post('mobile-app-settings/update-maintenance', [MobileAppSettingsController::class, 'updateMaintenance'])->name('mobile-app-settings.update-maintenance');
    Route::post('mobile-app-settings/update-api-keys', [MobileAppSettingsController::class, 'updateApiKeys'])->name('mobile-app-settings.update-api-keys');
    Route::post('mobile-app-settings/update-features', [MobileAppSettingsController::class, 'updateFeatures'])->name('mobile-app-settings.update-features');
    Route::post('mobile-app-settings/update-limits', [MobileAppSettingsController::class, 'updateLimits'])->name('mobile-app-settings.update-limits');
    Route::post('mobile-app-settings/update-info', [MobileAppSettingsController::class, 'updateInfo'])->name('mobile-app-settings.update-info');

    // Mobile Users Management Routes
    Route::get('mobile-users', [MobileUsersController::class, 'index'])->name('mobile-users.index');
    Route::get('mobile-users/{id}', [MobileUsersController::class, 'show'])->name('mobile-users.show');
    Route::post('mobile-users/{id}/suspend', [MobileUsersController::class, 'suspend'])->name('mobile-users.suspend');
    Route::post('mobile-users/{id}/reactivate', [MobileUsersController::class, 'reactivate'])->name('mobile-users.reactivate');
    Route::post('mobile-users/{id}/change-plan', [MobileUsersController::class, 'changePlan'])->name('mobile-users.change-plan');
    Route::post('mobile-users/{id}/extend-subscription', [MobileUsersController::class, 'extendSubscription'])->name('mobile-users.extend-subscription');
    Route::post('mobile-users/{id}/reset-password', [MobileUsersController::class, 'resetPassword'])->name('mobile-users.reset-password');
    Route::get('mobile-users/export/csv', [MobileUsersController::class, 'export'])->name('mobile-users.export');
    Route::get('mobile-users/statistics/ajax', [MobileUsersController::class, 'statistics'])->name('mobile-users.statistics');

    // Mobile App Subscriptions Routes
    Route::get('mobile-app-subscriptions', [MobileAppSubscriptionsController::class, 'index'])->name('mobile-app-subscriptions.index');
    Route::get('mobile-app-subscriptions/export/csv', [MobileAppSubscriptionsController::class, 'exportCsv'])->name('mobile-app-subscriptions.export.csv');
    Route::get('mobile-app-subscriptions/export/excel', [MobileAppSubscriptionsController::class, 'exportExcel'])->name('mobile-app-subscriptions.export.excel');

    // Mobile App Plans Management Routes
    Route::get('mobile-app-plans', [MobileAppPlansController::class, 'index'])->name('mobile-app-plans.index');
    Route::get('mobile-app-plans/create', [MobileAppPlansController::class, 'create'])->name('mobile-app-plans.create');
    Route::post('mobile-app-plans', [MobileAppPlansController::class, 'store'])->name('mobile-app-plans.store');
        Route::get('mobile-app-plans/comparison', [MobileAppPlansController::class, 'comparison'])->name('mobile-app-plans.comparison');
        Route::get('mobile-app-plans/export/csv', [MobileAppPlansController::class, 'export'])->name('mobile-app-plans.export');
    Route::get('mobile-app-plans/{id}/edit', [MobileAppPlansController::class, 'edit'])->name('mobile-app-plans.edit');
    Route::put('mobile-app-plans/{id}', [MobileAppPlansController::class, 'update'])->name('mobile-app-plans.update');
    Route::delete('mobile-app-plans/{id}', [MobileAppPlansController::class, 'destroy'])->name('mobile-app-plans.destroy');
    Route::post('mobile-app-plans/{id}/toggle-active', [MobileAppPlansController::class, 'toggleActive'])->name('mobile-app-plans.toggle-active');
    Route::get('mobile-app-plans/{id}/duplicate', [MobileAppPlansController::class, 'duplicate'])->name('mobile-app-plans.duplicate');
    Route::get('mobile-app-plans/{id}/statistics', [MobileAppPlansController::class, 'statistics'])->name('mobile-app-plans.statistics');
    Route::get('mobile-app-plans/{id}/chart-data', [MobileAppPlansController::class, 'chartData'])->name('mobile-app-plans.chart-data');

    // Mobile Analytics Dashboard Routes
    Route::get('mobile-analytics', [MobileAnalyticsController::class, 'index'])->name('mobile-analytics.index');
    Route::get('mobile-analytics/realtime', [MobileAnalyticsController::class, 'realtime'])->name('mobile-analytics.realtime');
    Route::get('mobile-analytics/export', [MobileAnalyticsController::class, 'export'])->name('mobile-analytics.export');

    // Push Notifications Management Routes
    Route::get('push-notifications', [\App\Http\Controllers\PushNotificationsController::class, 'index'])->name('push-notifications.index');
    Route::get('push-notifications/create', [\App\Http\Controllers\PushNotificationsController::class, 'create'])->name('push-notifications.create');
    Route::post('push-notifications', [\App\Http\Controllers\PushNotificationsController::class, 'store'])->name('push-notifications.store');
    Route::post('push-notifications/upload-image', [\App\Http\Controllers\PushNotificationsController::class, 'uploadImage'])->name('push-notifications.upload-image');
    Route::get('push-notifications/{id}', [\App\Http\Controllers\PushNotificationsController::class, 'show'])->name('push-notifications.show');
    Route::get('push-notifications/{id}/edit', [\App\Http\Controllers\PushNotificationsController::class, 'edit'])->name('push-notifications.edit');
    Route::put('push-notifications/{id}', [\App\Http\Controllers\PushNotificationsController::class, 'update'])->name('push-notifications.update');
    Route::delete('push-notifications/{id}', [\App\Http\Controllers\PushNotificationsController::class, 'destroy'])->name('push-notifications.destroy');
    Route::post('push-notifications/{id}/send', [\App\Http\Controllers\PushNotificationsController::class, 'send'])->name('push-notifications.send');
    Route::get('push-notifications/{id}/duplicate', [\App\Http\Controllers\PushNotificationsController::class, 'duplicate'])->name('push-notifications.duplicate');
    Route::post('push-notifications/{id}/cancel', [\App\Http\Controllers\PushNotificationsController::class, 'cancel'])->name('push-notifications.cancel');
    Route::get('push-notifications-preview-recipients', [\App\Http\Controllers\PushNotificationsController::class, 'previewRecipients'])->name('push-notifications.preview-recipients');
    Route::get('push-notifications-statistics', [\App\Http\Controllers\PushNotificationsController::class, 'statistics'])->name('push-notifications.statistics');

    // Legal Categories by Country Management Routes
    Route::get('legal-categories', [LegalLibraryController::class, 'categoriesByCountry'])->name('legal-categories.index');
    Route::post('legal-categories/{id}/toggle-visibility', [LegalLibraryController::class, 'toggleCategoryVisibility'])->name('legal-categories.toggle-visibility');
    Route::post('legal-categories/{id}/update-sort', [LegalLibraryController::class, 'updateCategorySort'])->name('legal-categories.update-sort');
    Route::get('legal-categories/country/{country}', [LegalLibraryController::class, 'filterByCountry'])->name('legal-categories.filter-by-country');

    // Mobile Legal Library Management Routes
    Route::get('mobile-legal-library', [MobileLegalLibraryController::class, 'index'])->name('mobile-legal-library.index');
    Route::post('mobile-legal-library/{id}/toggle', [MobileLegalLibraryController::class, 'toggleVisibility'])->name('mobile-legal-library.toggle');
    Route::post('mobile-legal-library/bulk-toggle', [MobileLegalLibraryController::class, 'bulkToggleVisibility'])->name('mobile-legal-library.bulk-toggle');
    Route::post('mobile-legal-library/category/{id}/sync', [MobileLegalLibraryController::class, 'syncCategory'])->name('mobile-legal-library.sync-category');
    Route::get('mobile-legal-library/force-sync', [MobileLegalLibraryController::class, 'forceSync'])->name('mobile-legal-library.force-sync');
    Route::get('mobile-legal-library/statistics', [MobileLegalLibraryController::class, 'statistics'])->name('mobile-legal-library.statistics');
    Route::get('mobile-legal-library/export', [MobileLegalLibraryController::class, 'export'])->name('mobile-legal-library.export');
    Route::get('mobile-legal-library/logs', [MobileLegalLibraryController::class, 'syncLogs'])->name('mobile-legal-library.logs');
    Route::post('mobile-legal-library/clear-old-logs', [MobileLegalLibraryController::class, 'clearOldLogs'])->name('mobile-legal-library.clear-old-logs');
    
    // Country Management Routes for Legal Library
    Route::post('mobile-legal-library/document/{id}/update-country', [MobileLegalLibraryController::class, 'updateDocumentCountry'])->name('mobile-legal-library.update-document-country');
    Route::post('mobile-legal-library/category/{id}/update-country', [MobileLegalLibraryController::class, 'updateCategoryCountry'])->name('mobile-legal-library.update-category-country');
    Route::post('mobile-legal-library/bulk-update-country', [MobileLegalLibraryController::class, 'bulkUpdateCountry'])->name('mobile-legal-library.bulk-update-country');
    Route::get('mobile-legal-library/country/{country}/statistics', [MobileLegalLibraryController::class, 'countryStatistics'])->name('mobile-legal-library.country-statistics');
    Route::get('mobile-legal-library/country/{country}/ai-context', [MobileLegalLibraryController::class, 'getCountryAIContext'])->name('mobile-legal-library.country-ai-context');

    // NOTE: Document Templates, Fiscal Resources routes are defined in Route::prefix() groups below (lines 920+)
    // Do NOT add duplicate routes here!

    // Calculators Management
    Route::get('calculators', [CalculatorController::class, 'index'])->name('calculators.index');
    Route::post('calculators', [CalculatorController::class, 'store'])->name('calculators.store');
    Route::delete('calculators/{id}', [CalculatorController::class, 'destroy'])->name('calculators.destroy');

    // Legal Alerts Management
    Route::get('legal-alerts', [LegalAlertController::class, 'index'])->name('legal-alerts.index');
    Route::post('legal-alerts', [LegalAlertController::class, 'store'])->name('legal-alerts.store');
    Route::delete('legal-alerts/{id}', [LegalAlertController::class, 'destroy'])->name('legal-alerts.destroy');
    Route::post('legal-alerts/{id}/publish', [LegalAlertController::class, 'publish'])->name('legal-alerts.publish');

    // Mobile Subscription Plans Admin
    Route::get('mobile-plans-admin', [MobilePlansAdminController::class, 'index'])->name('mobile-plans-admin.index');
    Route::get('mobile-plans-admin/create', [MobilePlansAdminController::class, 'create'])->name('mobile-plans-admin.create');
    Route::post('mobile-plans-admin', [MobilePlansAdminController::class, 'store'])->name('mobile-plans-admin.store');
    Route::get('mobile-plans-admin/{id}/edit', [MobilePlansAdminController::class, 'edit'])->name('mobile-plans-admin.edit');
    Route::put('mobile-plans-admin/{id}', [MobilePlansAdminController::class, 'update'])->name('mobile-plans-admin.update');
    Route::delete('mobile-plans-admin/{id}', [MobilePlansAdminController::class, 'destroy'])->name('mobile-plans-admin.destroy');
    Route::post('mobile-plans-admin/{id}/toggle-active', [MobilePlansAdminController::class, 'toggleActive'])->name('mobile-plans-admin.toggle-active');

    Route::get('generate/{template_name}', [AiTemplateController::class, 'create'])->name('generate');
    Route::post('generate/keywords/{id}', [AiTemplateController::class, 'getKeywords'])->name('generate.keywords');
    Route::post('generate/response', [AiTemplateController::class, 'AiGenerate'])->name('generate.response');
    Route::get('grammar/{template}', [AiTemplateController::class, 'grammar'])->name('grammar');
    Route::post('grammar/response', [AiTemplateController::class, 'grammarProcess'])->name('grammar.response');

    Route::resource('country', CountryController::class);

    Route::resource('state', StateController::class);

    Route::resource('city', CityController::class);

    Route::get('/get-country', [CountryStateCityController::class, 'getCountry'])->name('get.country');
    Route::post('/get-state', [CountryStateCityController::class, 'getState'])->name('get.state');
    Route::post('/get-city', [CountryStateCityController::class, 'getCity'])->name('get.city');
    Route::get('/get-all-city', [CountryStateCityController::class, 'getAllState'])->name('get.all.state');

    Route::get('change-language/{lang}', [LanguageController::class, 'changeLanquage'])->name('change.language');
    Route::get('manage-language/{lang}', [LanguageController::class, 'manageLanguage'])->name('manage.language');
    Route::post('store-language-data/{lang}', [LanguageController::class, 'storeLanguageData'])->name('store.language.data');
    Route::get('create-language', [LanguageController::class, 'createLanguage'])->name('create.language');
    Route::post('store-language', [LanguageController::class, 'storeLanguage'])->name('store.language');
    Route::delete('destroy-language/{lang}', [LanguageController::class, 'destroyLang'])->name('destroy.language');
    Route::post('disable-language', [LanguageController::class, 'disableLang'])->name('disablelanguage');

    Route::get('lead/list', [LeadController::class, 'lead_list'])->name('lead.list');
    Route::post('lead/order', [LeadController::class, 'order'])->name('lead.order');

    Route::get('lead/{id}/users', [LeadController::class, 'userEdit'])->name('lead.users.edit');
    Route::post('lead/{id}/users', [LeadController::class, 'userUpdate'])->name('lead.users.update');
    Route::delete('lead/{id}/users/{uid}', [LeadController::class, 'userDestroy'])->name('lead.users.destroy');

    Route::post('lead/{id}/file', [LeadController::class, 'fileUpload'])->name('lead.file.upload');
    Route::get('lead/{id}/file/{fid}', [LeadController::class, 'fileUpload'])->name('lead.file.download');
    Route::delete('lead/{id}/file/delete/{fid}', [LeadController::class, 'fileDelete'])->name('lead.file.delete');

    Route::get('lead/{id}/sources', [LeadController::class, 'sourceEdit'])->name('lead.sources.edit');
    Route::post('lead/{id}/sources', [LeadController::class, 'sourceUpdate'])->name('lead.sources.update');
    Route::delete('lead/{id}/sources/{uid}', [LeadController::class, 'sourceDestroy'])->name('lead.sources.destroy');

    Route::get('lead/{id}/discussions', [LeadController::class, 'discussionCreate'])->name('lead.discussions.create');
    Route::post('lead/{id}/discussions', [LeadController::class, 'discussionStore'])->name('lead.discussion.store');

    Route::get('lead/{id}/call', [LeadController::class, 'callCreate'])->name('lead.call.create');
    Route::post('lead/{id}/call', [LeadController::class, 'callStore'])->name('lead.call.store');
    Route::get('lead/{id}/call/{cid}/edit', [LeadController::class, 'callEdit'])->name('lead.call.edit');
    Route::post('lead/{id}/call/{cid}', [LeadController::class, 'callUpdate'])->name('lead.call.update');
    Route::delete('lead/{id}/call/{cid}', [LeadController::class, 'callDestroy'])->name('lead.call.destroy');

    Route::get('lead/{id}/email', [LeadController::class, 'emailCreate'])->name('lead.email.create');
    Route::post('lead/{id}/email', [LeadController::class, 'emailStore'])->name('lead.email.store');

    Route::get('lead/{id}/label', [LeadController::class, 'labels'])->name('lead.label');
    Route::post('lead/{id}/label', [LeadController::class, 'labelStore'])->name('lead.label.store');

    Route::get('lead/{id}/show_convert', [LeadController::class, 'showConvertToDeal'])->name('lead.convert.deal');
    Route::post('lead/{id}/convert', [LeadController::class, 'convertToDeal'])->name('lead.convert.to.deal');

    Route::post('lead/change-pipeline', [LeadController::class, 'changePipeline'])->name('lead.change.pipeline');

    Route::post('lead/{id}/note', [LeadController::class, 'noteStore'])->name('lead.note.store');

    Route::resource('lead', LeadController::class);

    Route::get('leads/export', [LeadController::class, 'exportFile'])->name('leads.export');
    Route::get('/import', [LeadController::class, 'fileImportExport'])->name('leads.file.import');
    Route::post('leads/import', [LeadController::class, 'fileImport'])->name('leads.import');

    Route::get('deal/list', [DealController::class, 'deal_list'])->name('deal.list');
    Route::post('deal/order', [DealController::class, 'order'])->name('deal.order');

    Route::get('deal/{id}/users', [DealController::class, 'userEdit'])->name('deal.users.edit');
    Route::post('deal/{id}/users', [DealController::class, 'userUpdate'])->name('deal.users.update');
    Route::delete('deal/{id}/users/{uid}', [DealController::class, 'userDestroy'])->name('deal.users.destroy');

    Route::post('deal/{id}/file', [DealController::class, 'fileUpload'])->name('deal.file.upload');
    Route::get('deal/{id}/file/{fid}', [DealController::class, 'fileDownload'])->name('deal.file.download');
    Route::delete('deal/{id}/file/delete/{fid}', [DealController::class, 'fileDelete'])->name('deal.file.delete');

    Route::get('deal/{id}/task', [DealController::class, 'taskCreate'])->name('deal.tasks.create');
    Route::post('deal/{id}/task', [DealController::class, 'taskStore'])->name('deal.tasks.store');
    Route::get('deal/{id}/task/{tid}/show', [DealController::class, 'taskShow'])->name('deal.tasks.show');
    Route::get('deal/{id}/task/{tid}/edit', [DealController::class, 'taskEdit'])->name('deal.tasks.edit');
    Route::post('deal/{id}/task/{tid}', [DealController::class, 'taskUpdate'])->name('deal.tasks.update');
    Route::post('deal/{id}/task_status/{tid}', [DealController::class, 'taskUpdateStatus'])->name('deal.tasks.update_status');
    Route::delete('deal/{id}/task/{tid}', [DealController::class, 'taskDestroy'])->name('deal.tasks.destroy');

    Route::get('deal/{id}/sources', [DealController::class, 'sourceEdit'])->name('deal.sources.edit');
    Route::post('deal/{id}/sources', [DealController::class, 'sourceUpdate'])->name('deal.sources.update');
    Route::delete('deal/{id}/sources/{uid}', [DealController::class, 'sourceDestroy'])->name('deal.sources.destroy');

    Route::get('deal/{id}/discussions', [DealController::class, 'discussionCreate'])->name('deal.discussions.create');
    Route::post('deal/{id}/discussions', [DealController::class, 'discussionStore'])->name('deal.discussion.store');

    Route::get('deal/{id}/call', [DealController::class, 'callCreate'])->name('deal.call.create');
    Route::post('deal/{id}/call', [DealController::class, 'callStore'])->name('deal.call.store');
    Route::get('deal/{id}/call/{cid}/edit', [DealController::class, 'callEdit'])->name('deal.call.edit');
    Route::post('deal/{id}/call/{cid}', [DealController::class, 'callUpdate'])->name('deal.call.update');
    Route::delete('deal/{id}/call/{cid}', [DealController::class, 'callDestroy'])->name('deal.call.destroy');

    Route::get('deal/{id}/email', [DealController::class, 'emailCreate'])->name('deal.email.create');
    Route::post('deal/{id}/email', [DealController::class, 'emailStore'])->name('deal.email.store');

    Route::get('deal/{id}/clients', [DealController::class, 'clientEdit'])->name('deal.clients.edit');
    Route::post('deal/{id}/clients', [DealController::class, 'clientUpdate'])->name('deal.clients.update');
    Route::delete('deal/{id}/clients/{uid}', [DealController::class, 'clientDestroy'])->name('deal.clients.destroy');

    Route::get('deal/{id}/labels', [DealController::class, 'labels'])->name('deal.labels');
    Route::post('deal/{id}/labels', [DealController::class, 'labelStore'])->name('deal.labels.store');

    Route::post('deal/change-pipeline', [DealController::class, 'changePipeline'])->name('deal.change.pipeline');

    Route::post('deal/{id}/note', [DealController::class, 'noteStore'])->name('deal.note.store');

    Route::resource('deal', DealController::class);

    Route::get('deals/export', [DealController::class, 'exportFile'])->name('deals.export');
    Route::get('/fileimport', [DealController::class, 'fileImportExport'])->name('deals.file.import');
    Route::post('deals/import', [DealController::class, 'fileImport'])->name('deals.import');

    // Route::get('ticket/create', [TicketController::class, 'create'])->name('tickets.create');
    // Route::post('ticket', [TicketController::class, 'store'])->name('tickets.store');
    // Route::get('ticket', [TicketController::class, 'index'])->name('tickets.index');
    // Route::get('ticket/{id}/edit', [TicketController::class, 'editTicket'])->name('tickets.edit');
    // Route::put('ticket/{id}/update', [TicketController::class, 'updateTicket'])->name('tickets.update');
    // Route::delete('ticket/{id}/destroy', [TicketController::class, 'destroy'])->name('tickets.destroy');
    Route::resource('tickets', TicketController::class);
    Route::delete('ticket-attachment/{tid}/destroy/{id}', [TicketController::class, 'attachmentDestroy'])->name('tickets.attachment.destroy');
    Route::get('ticket/export', [TicketController::class, 'exportFile'])->name('tickets.export');

    Route::post('ticket/{id}/note', [TicketController::class, 'storeNote'])->name('note.store');
    Route::post('ticket/{id}/conversion', [ConversionController::class, 'store'])->name('conversion.store');

    Route::get('faqs/index', [FaqController::class, 'index'])->name('faqs.index');
    Route::get('faqs/create', [FaqController::class, 'create'])->name('faqs.create');
    Route::post('faqs', [FaqController::class, 'store'])->name('faqs.store');
    Route::get('faqs/{id}/edit', [FaqController::class, 'edit'])->name('faqs.edit');
    Route::put('faqs/{id}/update', [FaqController::class, 'update'])->name('faqs.update');
    Route::delete('faqs/{id}/destroy', [FaqController::class, 'destroy'])->name('faqs.destroy');

    Route::get('knowledge', [KnowledgeController::class, 'index'])->name('knowledge');
    Route::get('knowledge/create', [KnowledgeController::class, 'create'])->name('knowledge.create');
    Route::post('knowledge', [KnowledgeController::class, 'store'])->name('knowledge.store');
    Route::get('knowledge/{id}/edit', [KnowledgeController::class, 'edit'])->name('knowledge.edit');
    Route::delete('knowledge/{id}/destroy', [KnowledgeController::class, 'destroy'])->name('knowledge.destroy');
    Route::put('knowledge/{id}/update', [KnowledgeController::class, 'update'])->name('knowledge.update');

    Route::get('knowledgecategory', [KnowledgebaseCategoryController::class, 'index'])->name('knowledgecategory');
    Route::get('knowledgecategory/create', [KnowledgebaseCategoryController::class, 'create'])->name('knowledgecategory.create');
    Route::post('knowledgecategory', [KnowledgebaseCategoryController::class, 'store'])->name('knowledgecategory.store');
    Route::get('knowledgecategory/{id}/edit', [KnowledgebaseCategoryController::class, 'edit'])->name('knowledgecategory.edit');
    Route::delete('knowledgecategory/{id}/destroy', [KnowledgebaseCategoryController::class, 'destroy'])->name('knowledgecategory.destroy');
    Route::put('knowledgecategory/{id}/update', [KnowledgebaseCategoryController::class, 'update'])->name('knowledgecategory.update');

    Route::get('category/create', [CategoryController::class, 'create'])->name('category.create');
    Route::post('category', [CategoryController::class, 'store'])->name('category.store');
    Route::get('category', [CategoryController::class, 'index'])->name('category.index');
    Route::get('category/{id}/edit', [CategoryController::class, 'edit'])->name('category.edit');
    Route::delete('category/{id}/destroy', [CategoryController::class, 'destroy'])->name('category.destroy');
    Route::put('category/{id}/update', [CategoryController::class, 'update'])->name('category.update');

    Route::resource('operating_hours', OperatinghoursController::class);

    Route::resource('priority', PriorityController::class);

    Route::resource('policiy', SLAPoliciyController::class);

    Route::any('ticket/custom/field', [TicketCustomFieldController::class, 'index'])->name('ticket.custom.field.index');
    Route::any('/custom-fields', [TicketCustomFieldController::class, 'storeCustomFields'])->name('custom-fields.store');

    //plan-payment
    Route::post('/stripe', [StripePaymentController::class, 'stripePost'])->name('stripe.post');

    Route::post('plan-pay-with-paypal', [PaypalController::class, 'planPayWithPaypal'])->name('plan.pay.with.paypal');
    Route::get('{id}/{amount}/plan-get-payment-status', [PaypalController::class, 'planGetPaymentStatus'])->name('plan.get.payment.status');

    Route::post('/plan-pay-with-paystack', [PaystackPaymentController::class, 'planPayWithPaystack'])->name('plan.pay.with.paystack');
    Route::get('/plan/paystack/{pay_id}/{plan_id}/', [PaystackPaymentController::class, 'getPaymentStatus'])->name('plan.paystack');

    Route::post('/plan-pay-with-flaterwave', [FlutterwavePaymentController::class, 'planPayWithFlutterwave'])->name('plan.pay.with.flaterwave');
    Route::get('/plan/flaterwave/{txref}/{plan_id}', [FlutterwavePaymentController::class, 'getPaymentStatus'])->name('plan.flaterwave');

    Route::post('/plan-pay-with-razorpay', [RazorpayPaymentController::class, 'planPayWithRazorpay'])->name('plan.pay.with.razorpay');
    Route::get('/plan/razorpay/{txref}/{plan_id}', [RazorpayPaymentController::class, 'getPaymentStatus'])->name('plan.razorpay');

    Route::post('/plan-pay-with-paytm', 'App\Http\Controllers\PaytmPaymentController@planPayWithPaytm')->name('plan.pay.with.paytm');
    Route::post('/plan/paytm/{plan_id}', [PaytmPaymentController::class, 'getPaymentStatus'])->name('plan.paytm');

    Route::post('/plan-pay-with-mercado', [MercadoPaymentController::class, 'planPayWithMercado'])->name('plan.pay.with.mercado');
    Route::get('/plan/mercado/{plan}/{amount}', [MercadoPaymentController::class, 'getPaymentStatus'])->name('plan.mercado');

    Route::post('/plan-pay-with-mollie', [MolliePaymentController::class, 'planPayWithMollie'])->name('plan.pay.with.mollie');
    Route::get('/plan/mollie/{plan}', [MolliePaymentController::class, 'getPaymentStatus'])->name('plan.mollie');

    Route::any('/plan-pay-with-skrill/{id}', [SkrillPaymentController::class, 'planPayWithSkrill'])->name('plan.pay.with.skrill');
    Route::get('/plan/skrill/{plan_id}', [SkrillPaymentController::class, 'getPaymentStatus'])->name('plan.skrill');

    Route::post('/plan-pay-with-coingate', [CoingatePaymentController::class, 'planPayWithCoingate'])->name('plan.pay.with.coingate');
    Route::get('/plan/coingate/{plan}', [CoingatePaymentController::class, 'getPaymentStatus'])->name('plan.coingate');

    Route::post('/planpayment', [PaymentWallController::class, 'planpay'])->name('paymentwall');
    Route::post('/paymentwall-payment/{plan}', [PaymentWallController::class, 'planPayWithPaymentWall'])->name('paymentwall.payment');
    Route::get('/plan/error/{flag}', [PaymentWallController::class, 'planerror'])->name('error.plan.show');

    Route::post('/plan-pay-with-toyyibpay', [ToyyibpayController::class, 'planPayWithToyyibpay'])->name('plan.pay.with.toyyibpay');
    Route::get('/plan-pay-with-toyyibpay/{id}/{amount}/{couponCode?}', [ToyyibpayController::class, 'planGetPaymentStatus'])->name('plan.toyyibpay');

    Route::post('payfast-plan', [PayfastController::class, 'index'])->name('payfast.payment');
    Route::get('payfast-plan/{success}', [PayfastController::class, 'success'])->name('payfast.payment.success');

    Route::post('plan-pay-with-bank', [BankTransferController::class, 'planPayWithbank'])->name('plan.pay.with.bank');
    Route::get('orders/show/{id}', [BankTransferController::class, 'show'])->name('order.show');
    Route::delete('/bank_transfer/{order}/', [BankTransferController::class, 'destroy'])->name('bank_transfer.destroy');
    Route::any('order_approve/{id}', [BankTransferController::class, 'orderapprove'])->name('order.approve');
    Route::any('order_reject/{id}', [BankTransferController::class, 'orderreject'])->name('order.reject');

    Route::post('iyzipay/prepare', [IyziPayController::class, 'initiatePayment'])->name('iyzipay.payment.init');
    Route::post('iyzipay/callback/plan/{id}/{amount}/{coupan_code?}', [IyzipayController::class, 'iyzipayCallback'])->name('iyzipay.payment.callback');

    Route::any('/sspay', [SspayController::class, 'SspayPaymentPrepare'])->name('plan.sspaypayment');
    Route::get('sspay-payment-plan/{plan_id}/{amount}/{couponCode}', [SspayController::class, 'SspayPlanGetPayment'])->name('plan.sspay.callback');

    Route::post('plan-pay-with-paytab', [PaytabController::class, 'planPayWithpaytab'])->name('plan.pay.with.paytab');
    Route::any('paytab-success/plan', [PaytabController::class, 'PaytabGetPayment'])->name('plan.paytab.success');

    Route::any('/payment/initiate', [BenefitPaymentController::class, 'initiatePayment'])->name('benefit.initiate');
    Route::any('call_back', [BenefitPaymentController::class, 'call_back'])->name('benefit.call_back');

    Route::post('cashfree/payments/', [CashfreeController::class, 'planPayWithcashfree'])->name('plan.pay.with.cashfree');
    Route::any('cashfree/payments/success', [CashfreeController::class, 'getPaymentStatus'])->name('plan.cashfree');

    Route::post('/aamarpay/payment', [AamarpayController::class, 'planPayWithpay'])->name('plan.pay.with.aamarpay');
    Route::any('/aamarpay/success/{data}', [AamarpayController::class, 'getPaymentStatus'])->name('plan.aamarpay');

    Route::any('/paytr/payment', [PaytrController::class, 'PlanpayWithPaytr'])->name('plan.pay.with.paytr');
    Route::any('/paytr/success/', [PaytrController::class, 'paytrsuccessCallback'])->name('pay.paytr.success');

    Route::get('/plan/yookassa/payment', [YooKassaController::class, 'planPayWithYooKassa'])->name('plan.pay.with.yookassa');
    Route::get('/plan/yookassa/{plan}', [YooKassaController::class, 'planGetYooKassaStatus'])->name('plan.get.yookassa.status');

    Route::any('/midtrans', [MidtransController::class, 'planPayWithMidtrans'])->name('plan.get.midtrans');
    Route::any('/midtrans/callback', [MidtransController::class, 'planGetMidtransStatus'])->name('plan.get.midtrans.status');

    Route::any('/xendit/payment', [XenditPaymentController::class, 'planPayWithXendit'])->name('plan.xendit.payment');
    Route::any('/xendit/payment/status', [XenditPaymentController::class, 'planGetXenditStatus'])->name('plan.xendit.status');

    Route::post('plan-payhere-payment', [PayHereController::class, 'planPayWithPayHere'])->name('plan.payhere.payment');
    Route::any('/plan-payhere-status', [PayHereController::class, 'planGetPayHereStatus'])->name('plan.payhere.status');

    Route::any('plan-paiementpro-payment', [PaiementProController::class, 'planPayWithPaiementpro'])->name('plan.pay.with.paiementpro');
    Route::any('/plan-paiementpro-status/{plan_id}', [PaiementProController::class, 'planGetPaiementproStatus'])->name('plan.paiementpro.status');

    Route::any('plan-nepalste-payment/', [NepalstePaymnetController::class, 'planPayWithNepalste'])->name('plan.pay.with.nepalste');
    Route::get('plan-nepalste-status/', [NepalstePaymnetController::class, 'planGetNepalsteStatus'])->name('plan.nepalste.status');
    Route::get('plan-nepalste-cancel/', [NepalstePaymnetController::class, 'planGetNepalsteCancel'])->name('plan.nepalste.cancel');

    Route::any('plan-cinetpay-payment', [CinetPayController::class, 'planPayWithCinetpay'])->name('plan.pay.with.cinetpay');
    Route::any('plan-cinetpay-return', [CinetPayController::class, 'planCinetPayReturn'])->name('plan.cinetpay.return');
    Route::any('plan-cinetpay-notify', [CinetPayController::class, 'planCinetPayNotify'])->name('plan.cinetpay.notify');

    Route::any('plan-fedapay-payment', [FedapayController::class, 'planPayWithFedapay'])->name('plan.pay.with.fedapay');
    Route::any('plan-fedapay-status', [FedapayController::class, 'planGetFedapayStatus'])->name('plan.fedapay.status');

    Route::any('plan-pay-with-tap', [TapPaymentController::class, 'planPayWithTap'])->name('plan.pay.with.tap');
    Route::any('plan-get-tap-status/{plan_id}', [TapPaymentController::class, 'planGetTapStatus'])->name('plan.get.tap.status');

    Route::post('plan-pay-with-authorizenet', [AuthorizeNetController::class, 'planPayWithAuthorizeNet'])->name('plan.pay.with.authorizenet');
    Route::any('plan-get-authorizenet-status', [AuthorizeNetController::class, 'planGetAuthorizeNetStatus'])->name('plan.get.authorizenet.status');

    Route::post('plan-pay-with/ozow', [OzowController::class, 'planPayWithOzow'])->name('plan.pay.with.ozow');
    Route::get('plan-get-ozow-status/{plan_id}', [OzowController::class, 'planGetOzowStatus'])->name('plan.get.ozow.status');

    Route::post('plan-pay-with-khalti', [KhaltiPaymentController::class, 'planPayWithKhalti'])->name('plan.pay.with.khalti');
    Route::any('plan-get-khalti-status', [KhaltiPaymentController::class, 'planGetKhaltiStatus'])->name('plan.get.khalti.status');


    // Route::get('/settings/powertranz/{id}', [PowerTranzController::class, 'show'])->name('settings.powertranz');
    // Route::post('/payment/success', [PowerTranzController::class, 'paymentSuccess'])->name('payment.success');


    // ================================2fa authentication======================

    Route::any('/generateSecret', [GoogleAuthenticationController::class, 'generate2faSecret'])->name('generate2faSecret');
    Route::post('/enable2fa', [GoogleAuthenticationController::class, 'enable2fa'])->name('enable2fa');
    Route::post('/disable2fa', [GoogleAuthenticationController::class, 'disable2fa'])->name('disable2fa');

    Route::get('/2fa', [GoogleAuthenticationController::class, 'show2faForm'])->name('2fa.index');
    Route::post('/2fa', [GoogleAuthenticationController::class, 'verify2fa'])->name('2faVerify');
});

Route::group(['middleware' => ['XSS']], function () {

    Route::any('/cookie-consent', [SettingController::class, 'CookieConsent'])->name('cookie-consent');

    Route::controller(HomeController::class)->group(function () {
        Route::get('user/ticket/create', 'index')->name('user.ticket.create');
        Route::post('home', 'store')->name('home.store');
        Route::get('user/ticket/search', 'search')->name('user.ticket.search');
        Route::post('search', 'ticketSearch')->name('ticket.search');
        Route::get('tickets/{id}', 'view')->name('home.view');
        Route::post('ticket/{id}', 'reply')->name('user.ticket.reply');
        Route::get('user/faq', 'faq')->name('user.faq');
        Route::get('user/knowledge', 'knowledge')->name('user.knowledge');
        Route::get('knowledgedesc', 'knowledgeDescription')->name('knowledgedesc');
    });

    Route::get('/bills/pay/{bill_id}', [BillController::class, 'payinvoice'])->name('pay.invoice');
    Route::POST('bills/getclientdetail', [BillController::class, 'getClientDetail'])->name('get.client.detail');
    Route::POST('bills/getadvocatedetail', [BillController::class, 'getadvocateDetail'])->name('get.advocate.detail');

    // invoice-pay
    Route::post('bills/{id}/payment', [StripePaymentController::class, 'addpayment'])->name('invoice.payment');

    Route::post('bills/{id}/bill-with-paypal', [PaypalController::class, 'PayWithPaypal'])->name('bill.with.paypal');
    Route::get('{id}/get-payment-status/{amount}', [PaypalController::class, 'GetPaymentStatus'])->name('get.payment.status');

    Route::post('/invoice-pay-with-paystack', [PaystackPaymentController::class, 'invoicePayWithPaystack'])->name('invoice.pay.with.paystack');
    Route::get('/invoice/paystack/{invoice_id}/{amount}/{pay_id}', [PaystackPaymentController::class, 'getInvoicePaymentStatus'])->name('invoice.paystack');

    Route::post('/invoice-pay-with-flaterwave', [FlutterwavePaymentController::class, 'invoicePayWithFlutterwave'])->name('invoice.pay.with.flaterwave');
    Route::get('/invoice/flaterwave/{txref}/{invoice_id}', [FlutterwavePaymentController::class, 'getInvoicePaymentStatus'])->name('invoice.flaterwave');

    Route::post('/invoice-pay-with-razorpay', [RazorpayPaymentController::class, 'invoicePayWithRazorpay'])->name('invoice.pay.with.razorpay');
    Route::get('/invoice/razorpay/{txref}/{invoice_id}', [RazorpayPaymentController::class, 'getInvoicePaymentStatus'])->name('invoice.razorpay');

    Route::any('/invoice-pay-with-mercado', [MercadoPaymentController::class, 'invoicePayWithMercado'])->name('invoice.pay.with.mercado');
    Route::any('/invoice/mercado/{invoice}', [MercadoPaymentController::class, 'getInvoicePaymentStatus'])->name('invoice.mercado');

    Route::post('/invoice-pay-with-paytm', [PaytmPaymentController::class, 'invoicePayWithPaytm'])->name('invoice.pay.with.paytm');
    Route::post('/invoice/paytm/{invoice}', [PaytmPaymentController::class, 'getInvoicePaymentStatus'])->name('invoice.paytm');

    Route::post('/invoice-pay-with-mollie', [MolliePaymentController::class, 'invoicePayWithMollie'])->name('invoice.pay.with.mollie');
    Route::get('/invoice/mollie/{invoice}', [MolliePaymentController::class, 'getInvoicePaymentStatus'])->name('invoice.mollie');

Route::any('/invoice-pay-with-skrill', [SkrillPaymentController::class, 'invoicePayWithSkrill'])->name('invoice.pay.with.skrill');
    Route::get('/invoice/skrill/{invoice}', [SkrillPaymentController::class, 'getInvoicePaymentStatus'])->name('invoice.skrill');

    Route::post('/invoice-pay-with-coingate', [CoingatePaymentController::class, 'invoicePayWithCoingate'])->name('invoice.pay.with.coingate');
    Route::get('/invoice/coingate/{invoice}', [CoingatePaymentController::class, 'getInvoicePaymentStatus'])->name('invoice.coingate');

    Route::post('/invoicepayment', [PaymentWallController::class, 'invoicePayWithPaymentwall'])->name('paymentwall.invoice');
    Route::post('/invoice-pay-with-paymentwall/{invoice}', [PaymentWallController::class, 'getInvoicePaymentStatus'])->name('invoice-pay-with-paymentwall');
    Route::any('/invoice/error/{flag}/{invoice_id}', [PaymentWallController::class, 'invoiceerror'])->name('error.invoice.show');

    Route::post('/invoice-with-toyyibpay', [ToyyibpayController::class, 'invoicepaywithtoyyibpay'])->name('invoice.with.toyyibpay');
    Route::get('/invoice-toyyibpay-status/{amount}/{invoice_id}', [ToyyibpayController::class, 'invoicetoyyibpaystatus'])->name('invoice.toyyibpay.status');

    Route::post('/invoice-with-payfast', [PayfastController::class, 'invoicepaywithpayfast'])->name('invoice.with.payfast');
    Route::get('/invoice-payfast-status/{invoice_id}', [PayfastController::class, 'invoicepayfaststatus'])->name('invoice.payfast.status');

    Route::any('/pay-with-bank', [BankTransferController::class, 'invoicePayWithbank'])->name('invoice.pay.with.bank');
    Route::get('bankpayment/show/{id}', [BankTransferController::class, 'bankpaymentshow'])->name('bankpayment.show');
    Route::delete('invoice/bankpayment/{id}/delete', [BankTransferController::class, 'invoicebankPaymentDestroy'])->name('invoice.bankpayment.delete');
    Route::post('/invoice/status/{id}', [BankTransferController::class, 'invoicebankstatus'])->name('invoice.status');

    Route::post('/invoice-with-iyzipay', [IyziPayController::class, 'invoicepaywithiyzipay'])->name('invoice.with.iyzipay');
    Route::post('/invoice-iyzipay-status/{invoice_id}/{amount}', [IyziPayController::class, 'invoiceiyzipaystatus'])->name('invoice.iyzipay.status');

    Route::post('/customer-pay-with-sspay', [SspayController::class, 'invoicepaywithsspaypay'])->name('customer.pay.with.sspay');
    Route::get('/customer/sspay/{invoice}/{amount}', [SspayController::class, 'getInvoicePaymentStatus'])->name('customer.sspay');

    Route::post('invoice-with-paytab/', [PaytabController::class, 'invoicePayWithpaytab'])->name('pay.with.paytab');
    Route::any('invoice-paytab-status/{invoice}/{amount}', [PaytabController::class, 'PaytabGetPaymentCallback'])->name('invoice.paytab.status');

    Route::post('invoice-with-benefit/', [BenefitPaymentController::class, 'invoicePayWithbenefit'])->name('pay.with.benefit');
    Route::any('invoice-benefit-status/{invoice_id}/{amount}', [BenefitPaymentController::class, 'getInvociePaymentStatus'])->name('invoice.benefit.status');

    Route::any('invoice-with-cashfree/', [CashfreeController::class, 'invoicePayWithcashfree'])->name('pay.with.cashfree');
    Route::any('invoice-cashfree-status/', [CashfreeController::class, 'getInvociePaymentStatus'])->name('invoice.cashfree.status');

    Route::post('invoice-with-aamarpay/', [AamarpayController::class, 'invoicePayWithaamarpay'])->name('pay.with.aamarpay');
    Route::any('invoice-aamarpay-status/{data}', [AamarpayController::class, 'getInvociePaymentStatus'])->name('invoice.aamarpay.status');

    Route::post('invoice-with-paytr/', [PaytrController::class, 'invoicePayWithpaytr'])->name('invoice.with.paytr');
    Route::any('invoice-paytr-status/', [PaytrController::class, 'getInvociePaymentStatus'])->name('invoice.paytr.status');

    Route::post('invoice-with-yookassa/', [YooKassaController::class, 'invoicePayWithYookassa'])->name('invoice.with.yookassa');
    Route::any('invoice-yookassa-status/', [YooKassaController::class, 'getInvociePaymentStatus'])->name('invoice.yookassa.status');

    Route::any('invoice-with-midtrans/', [MidtransController::class, 'invoicePayWithMidtrans'])->name('invoice.with.midtrans');
    Route::any('invoice-midtrans-status/', [MidtransController::class, 'getInvociePaymentStatus'])->name('invoice.midtrans.status');

    Route::any('/invoice-with-xendit', [XenditPaymentController::class, 'invoicePayWithXendit'])->name('invoice.with.xendit');
    Route::any('/invoice-xendit-status', [XenditPaymentController::class, 'getInvociePaymentStatus'])->name('invoice.xendit.status');

    Route::any('invoice-payhere-payment', [PayHereController::class, 'invoicePayWithPayHere'])->name('invoice.payhere.payment');
    Route::get('/invoice-payhere-status', [PayHereController::class, 'invoiceGetPayHereStatus'])->name('invoice.payhere.status');

    Route::any('invoice-paiementpro-payment', [PaiementProController::class, 'invoicePayWithPaiementpro'])->name('invoice.pay.with.paiementpro');
    Route::any('/invoice-paiementpro-status', [PaiementProController::class, 'invoiceGetPaiementproStatus'])->name('invoice.paiementpro.status');

    Route::post('invoice-nepalste-payment/{id}', [NepalstePaymnetController::class, 'invoicePayWithNepalste'])->name('invoice.with.nepalste');
    Route::get('invoice-nepalste-status/{id}/{amt?}', [NepalstePaymnetController::class, 'invoiceGetNepalsteStatus'])->name('invoice.nepalste.status');
    Route::get('invoice-nepalste-cancel/', [NepalstePaymnetController::class, 'invoiceGetNepalsteCancel'])->name('invoice.nepalste.cancel');

    Route::any('invoice-cinetpay-payment', [CinetPayController::class, 'invoicePayWithCinetPay'])->name('invoice.pay.with.cinetpay');
    Route::any('invoice-cinetpay-return/{id}/{amt?}', [CinetPayController::class, 'invoiceCinetPayReturn'])->name('invoice.cinetpay.return');
    Route::any('invoice-cinetpay-notify/{id?}', [CinetPayController::class, 'invoiceCinetPayNotify'])->name('invoice.cinetpay.notify');

    Route::any('invoice-fedapay-payment', [FedapayController::class, 'invoicePayWithFedapay'])->name('invoice.with.fedapay');
    Route::any('invoice-fedapay-status/{id}/{amt?}', [FedapayController::class, 'invoiceGetFedapayStatus'])->name('invoice.fedapay.status');

    Route::any('invoice-tap-payment', [TapPaymentController::class, 'invoicePayWithTap'])->name('invoice.with.tap');
    Route::any('invoice-tap-status/{id}/{amount?}', [TapPaymentController::class, 'invoiceGetTapStatus'])->name('invoice.tap.status');

    Route::any('invoice-authorizenet-payment', [AuthorizeNetController::class, 'invoicePayWithAuthorizeNet'])->name('invoice.with.authorizenet');
    Route::any('invoice-authorizenet-status/{id}/{amount?}', [AuthorizeNetController::class, 'invoiceGetAuthorizeNetStatus'])->name('invoice.authorizenet.status');

    Route::any('invoice-ozow-payment', [OzowController::class, 'invoicePayWithOzow'])->name('invoice.with.ozow');
    Route::any('invoice-ozow-status/{id}/{amount?}', [OzowController::class, 'invoiceGetOzowStatus'])->name('invoice.ozow.status');

    Route::any('invoice-khalti-payment', [KhaltiPaymentController::class, 'invoicePayWithKhalti'])->name('invoice.with.khalti');
    Route::any('invoice-khalti-status', [KhaltiPaymentController::class, 'invoiceGetKhaltiStatus'])->name('invoice.khalti.status');
});

// powertranz
Route::get('powertranz/show/{id}', [PowerTranzController::class, 'show'])->name('powertranz.show');
Route::post('powertranz/response', [PowerTranzController::class, 'response'])->name('powertranz.response');

Route::any('plan-pay-with-powertranz', [PowerTranzController::class, 'planPayWithPowerTranz'])->name('plan.pay.with.powertranz');
Route::post('powertranz/status/{order_id}', [PowerTranzController::class, 'status'])->name('powertranz.status');


Route::get('powertranz/bill/{id}', [PowerTranzController::class, 'showBillPaymentForm'])->name('powertranz.bill.show');
Route::post('powertranz/bill/pay', [PowerTranzController::class, 'payBillWithPowerTranz'])->name('powertranz.bill.pay');
Route::post('powertranz/bill/response', [PowerTranzController::class, 'billResponse'])->name('powertranz.bill.response');
Route::post('powertranz/bill/status/{order_id}', [PowerTranzController::class, 'billStatus'])->name('powertranz.bill.status');
Route::post('/settings/store', [PowerTranzController::class, 'settingConfig'])->name('powertranz.setting.store')->middleware(['auth', 'verified', 'PlanModuleCheck:PowerTranz']);

// payu
Route::post('payu/plan/pay', [PayUPaymentController::class, 'planPayWithPayU'])->name('payu.plan.pay');
Route::any('payu/plan/response', [PayUPaymentController::class, 'response'])->name('payu.response');
Route::any('payu/plan/failure', [PayUPaymentController::class, 'failure'])->name('payu.failure');


Route::any('payu/bill/pay/{id}', [PayUPaymentController::class, 'payBillWithPayU'])->name('payu.bill.pay');
Route::any('payu/bill/response/{bill_id}', [PayUPaymentController::class, 'billResponse'])->name('payu.bill.response');
Route::any('payu/bill/failure', [PayUPaymentController::class, 'billFailure'])->name('payu.bill.failure');
Route::post('payu/settings', [PayUPaymentController::class, 'settingConfig'])->name('payu.settings.store');

/*
|--------------------------------------------------------------------------
| DOSSY ENTERPRISE SYSTEM ROUTES - Admin Interface
|--------------------------------------------------------------------------
| Routes for managing Enterprise features: Templates, Fiscal Resources,
| Calculators, Legal Alerts, Multi-Accounts, and Subscription Plans
|
*/

// Document Templates Management (Modèles d'Actes et Contrats)
// MOVED OUTSIDE admin prefix to match view route calls
Route::middleware(['auth', 'XSS'])->prefix('admin')->name('')->group(function () {
    Route::prefix('document-templates')->name('document-templates.')->group(function () {
        Route::get('/', [App\Http\Controllers\DocumentTemplateController::class, 'index'])->name('index');
        Route::get('/bulk-upload', [App\Http\Controllers\DocumentTemplateController::class, 'bulkUploadForm'])->name('bulk-upload.form');
        Route::post('/bulk-upload', [App\Http\Controllers\DocumentTemplateController::class, 'bulkUploadStore'])->name('bulk-upload.store');
        Route::post('/', [App\Http\Controllers\DocumentTemplateController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [App\Http\Controllers\DocumentTemplateController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\DocumentTemplateController::class, 'update'])->name('update');
        Route::post('/categories/store', [App\Http\Controllers\DocumentTemplateController::class, 'storeCategory'])->name('categories.store');
        Route::get('/{id}/download', [App\Http\Controllers\DocumentTemplateController::class, 'download'])->name('download');
        Route::post('/{id}/toggle-mobile', [App\Http\Controllers\DocumentTemplateController::class, 'toggleMobileVisibility'])->name('toggle-mobile');
        Route::delete('/{id}', [App\Http\Controllers\DocumentTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete', [App\Http\Controllers\DocumentTemplateController::class, 'bulkDelete'])->name('bulk-delete');
        Route::get('/export', [App\Http\Controllers\DocumentTemplateController::class, 'export'])->name('export');
    });

    // Fiscal & Social Resources Management
    // MOVED OUTSIDE admin prefix to match view route calls
    Route::prefix('fiscal-resources')->name('fiscal-resources.')->group(function () {
        Route::get('/', [App\Http\Controllers\FiscalSocialResourceController::class, 'index'])->name('index');
        Route::get('/bulk-upload', [App\Http\Controllers\FiscalSocialResourceController::class, 'bulkUploadForm'])->name('bulk-upload.form');
        Route::post('/bulk-upload', [App\Http\Controllers\FiscalSocialResourceController::class, 'bulkUploadStore'])->name('bulk-upload.store');
        Route::get('/create', [App\Http\Controllers\FiscalSocialResourceController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\FiscalSocialResourceController::class, 'store'])->name('store');
        Route::post('/categories/store', [App\Http\Controllers\FiscalSocialResourceController::class, 'storeCategory'])->name('categories.store');
        Route::get('/{id}', [App\Http\Controllers\FiscalSocialResourceController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [App\Http\Controllers\FiscalSocialResourceController::class, 'edit'])->name('edit');
        Route::get('/{id}/download', [App\Http\Controllers\FiscalSocialResourceController::class, 'download'])->name('download');
        Route::put('/{id}', [App\Http\Controllers\FiscalSocialResourceController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\FiscalSocialResourceController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-mobile', [App\Http\Controllers\FiscalSocialResourceController::class, 'toggleMobileVisibility'])->name('toggle-mobile');
        Route::get('/export', [App\Http\Controllers\FiscalSocialResourceController::class, 'export'])->name('export');
        
        // Salary Grids
        Route::get('/salary-grids', [App\Http\Controllers\FiscalSocialResourceController::class, 'salaryGrids'])->name('salary-grids');
        Route::post('/salary-grids', [App\Http\Controllers\FiscalSocialResourceController::class, 'storeSalaryGrid'])->name('salary-grids.store');
        
        // Tax Parameters
        Route::get('/tax-parameters', [App\Http\Controllers\FiscalSocialResourceController::class, 'taxParameters'])->name('tax-parameters');
        Route::post('/tax-parameters', [App\Http\Controllers\FiscalSocialResourceController::class, 'storeTaxParameter'])->name('tax-parameters.store');
    });
});

// Additional Admin Routes (with admin. prefix)
Route::middleware(['auth', 'XSS'])->prefix('admin')->name('admin.')->group(function () {
    
    // Calculators & Simulators Management
    Route::prefix('calculators')->name('calculators.')->group(function () {
        Route::get('/', [App\Http\Controllers\CalculatorController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\CalculatorController::class, 'store'])->name('store');
        Route::put('/{id}', [App\Http\Controllers\CalculatorController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\CalculatorController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-active', [App\Http\Controllers\CalculatorController::class, 'toggleActive'])->name('toggle-active');
        Route::get('/logs', [App\Http\Controllers\CalculatorController::class, 'logs'])->name('logs');
    });

    // Legal Alerts Management
    Route::prefix('legal-alerts')->name('legal-alerts.')->group(function () {
        Route::get('/', [App\Http\Controllers\LegalAlertController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\LegalAlertController::class, 'store'])->name('store');
        Route::put('/{id}', [App\Http\Controllers\LegalAlertController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\LegalAlertController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/send', [App\Http\Controllers\LegalAlertController::class, 'send'])->name('send');
        Route::get('/recipients', [App\Http\Controllers\LegalAlertController::class, 'recipients'])->name('recipients');
    });

    // Mobile Subscription Plans Management (Enhanced with new prices)
    Route::prefix('mobile-plans')->name('mobile-plans.')->group(function () {
        Route::get('/', [App\Http\Controllers\MobilePlansAdminController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\MobilePlansAdminController::class, 'store'])->name('store');
        Route::put('/{id}', [App\Http\Controllers\MobilePlansAdminController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\MobilePlansAdminController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-active', [App\Http\Controllers\MobilePlansAdminController::class, 'toggleActive'])->name('toggle-active');
        Route::get('/export', [App\Http\Controllers\MobilePlansAdminController::class, 'export'])->name('export');
    });

    // OpenAI Integration Diagnostics
    Route::prefix('diagnostics')->name('diagnostics.')->group(function () {
        Route::get('/openai-integration', [OpenAIDiagnosticsController::class, 'index'])->name('openai-integration');
    });
});

// Public routes for mobile payment callbacks (no auth required)
Route::name('mobile.')->group(function () {
    Route::get('/mobile/payment/callback', [\App\Http\Controllers\Api\Mobile\PaymentController::class, 'paymentCallback'])->name('payment.callback');
});