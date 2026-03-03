<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\ChatController;
use App\Http\Controllers\Api\Mobile\ConfigController;
use App\Http\Controllers\Api\Mobile\DocumentController;
use App\Http\Controllers\Api\Mobile\SubscriptionController;
use App\Http\Controllers\Api\Mobile\ReferralController;
use App\Http\Controllers\Api\Mobile\TemplateApiController;
use App\Http\Controllers\Api\Mobile\FiscalResourceApiController;
use App\Http\Controllers\Api\Mobile\CalculatorApiController;
use App\Http\Controllers\Api\Mobile\LegalAlertApiController;
use App\Http\Controllers\Api\Mobile\SubscriptionApiController;
use App\Http\Controllers\Api\Mobile\EnterpriseApiController;
use App\Http\Controllers\Api\Mobile\DiagnosticController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\Mobile\CouponApiController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Dossy IA Mobile App API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no authentication required)
Route::prefix('mobile')->group(function () {
    // Health check / Ping endpoint
    Route::get('/', function () {
        return response()->json([
            'success' => true,
            'message' => 'DOSSY CHAT IA API - Mobile endpoint',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
            'endpoints' => [
                'register' => 'POST /api/mobile/register',
                'login' => 'POST /api/mobile/login',                'forgot-password' => 'POST /api/mobile/password/forgot',                'config' => 'GET /api/mobile/config',
            ],
        ], 200);
    });
    
    // App Configuration (checked on app startup)
    Route::get('/config', [ConfigController::class, 'getConfig']);
    
    // Authentication
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    // Forgot password (send reset link to email)
    Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])->middleware('throttle:10,1');
    
    // Referral validation (for registration)
    Route::post('/referral/validate', [ReferralController::class, 'validateReferralCode'])->middleware('throttle:10,1');
    
    // Plans (viewable without auth)
    Route::get('/subscriptions/plans', [SubscriptionController::class, 'getPlans']);
});

// Protected routes (require authentication) - SUBSCRIPTION ENDPOINTS
Route::prefix('mobile')->middleware('auth:sanctum')->group(function () {
    // User Profile with statistics
    Route::get('/user/profile', [SubscriptionController::class, 'getUserProfile']);
    
    // Increment user statistics
    Route::post('/user/stats/increment', [SubscriptionController::class, 'incrementUserStats']);
});

// Protected routes (require authentication)
Route::prefix('mobile')->middleware('auth:sanctum')->group(function () {
    
    // Authentication & Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    
    // Chat / Conversations
    Route::prefix('chat')->group(function () {
        Route::post('/', [ChatController::class, 'sendMessage']); // Mobile app simple endpoint
        Route::post('/conversation', [ChatController::class, 'createConversation']);
        Route::get('/conversations', [ChatController::class, 'getConversations']);
        Route::get('/conversation/{id}/messages', [ChatController::class, 'getMessages']);
        Route::get('/history', [ChatController::class, 'getChatHistory']);
        Route::post('/send', [ChatController::class, 'sendMessage']);
        Route::delete('/conversation/{id}', [ChatController::class, 'deleteConversation']);
        
        // Generated documents from chat
        Route::get('/generated-documents', [ChatController::class, 'getGeneratedDocuments']);
        Route::get('/generated-documents/{id}/download', [ChatController::class, 'downloadGeneratedDocument'])
            ->name('api.mobile.generated-document.download');
    });
    
    // Documents
    Route::prefix('documents')->group(function () {
        // User documents (submitted)
        Route::post('/upload', [DocumentController::class, 'upload']);
        Route::get('/my-documents', [DocumentController::class, 'getUserDocuments']);
        Route::delete('/{id}', [DocumentController::class, 'deleteDocument']);
        
        // Anonymisation automatique
        Route::post('/anonymize', [DocumentController::class, 'anonymize']);
        
        // Legal library
        Route::post('/search', [DocumentController::class, 'searchLegalDocuments']);
        Route::get('/categories', [DocumentController::class, 'getLegalCategories']);
        Route::get('/legal/{id}/download', [DocumentController::class, 'downloadLegalDocument']);
        Route::post('/legal/{id}/view', [DocumentController::class, 'viewLegalDocument']);
    });
    
    // Subscription
    Route::prefix('subscription')->group(function () {
        Route::get('/current', [SubscriptionController::class, 'getCurrentSubscription']);
        Route::post('/initiate', [SubscriptionController::class, 'initiateSubscription']);
        Route::post('/activate', [SubscriptionController::class, 'activateSubscription']);
        Route::post('/cancel', [SubscriptionController::class, 'cancelSubscription']);
        Route::get('/payments', [SubscriptionController::class, 'getPaymentHistory']);
    });
    
    // Payment (Flutterwave integration)
    Route::prefix('payment')->group(function () {
        Route::post('/initiate', [\App\Http\Controllers\Api\Mobile\PaymentController::class, 'initiatePayment']);
    });
    
    // Referral
    Route::prefix('referral')->group(function () {
        Route::get('/code', [ReferralController::class, 'getReferralCode']);
        Route::get('/history', [ReferralController::class, 'getReferralHistory']);
        Route::get('/rewards', [ReferralController::class, 'getReferralRewards']);
    });
    
    // User's plan limits
    Route::get('/limits', [ConfigController::class, 'getPlanLimits']);
    
    // Diagnostic endpoint (pour debug)
    Route::get('/diagnostic/library-data', [DiagnosticController::class, 'checkLibraryData']);
    
    // ENTERPRISE FEATURES
    
    // Templates (Professional & Enterprise plans)
    Route::prefix('templates')->group(function () {
        Route::get('/', [TemplateApiController::class, 'index']);
        Route::get('/{id}', [TemplateApiController::class, 'show']);
        Route::get('/{id}/download', [TemplateApiController::class, 'download']);
    });
    
    // Fiscal & Social Resources (Professional & Enterprise plans)
    Route::prefix('fiscal-resources')->group(function () {
        Route::get('/', [FiscalResourceApiController::class, 'index']);
        Route::get('/{id}/download', [FiscalResourceApiController::class, 'download']);
        Route::post('/{id}/view', [FiscalResourceApiController::class, 'view']);
        Route::get('/salary-grids', [FiscalResourceApiController::class, 'salaryGrids']);
        Route::get('/tax-parameters', [FiscalResourceApiController::class, 'taxParameters']);
    });
    
    // Calculators (Professional & Enterprise plans)
    Route::prefix('calculators')->group(function () {
        Route::get('/', [CalculatorApiController::class, 'index']);
        Route::post('/{id}/calculate', [CalculatorApiController::class, 'calculate']);
        Route::get('/history', [CalculatorApiController::class, 'history']);
    });
    
    // Legal Alerts (Professional & Enterprise plans)
    Route::prefix('legal-alerts')->group(function () {
        Route::get('/', [LegalAlertApiController::class, 'index']);
        Route::post('/{id}/mark-read', [LegalAlertApiController::class, 'markRead']);
    });
    
    // Subscription Plans (updated with new prices)
    Route::prefix('subscription-plans')->group(function () {
        Route::get('/', [SubscriptionApiController::class, 'plans']);
        Route::get('/current', [SubscriptionApiController::class, 'currentPlan']);
    });
    
    // Enterprise Multi-Accounts (Cabinet/Enterprise plan only)
    Route::prefix('enterprise')->group(function () {
        Route::get('/dashboard', [EnterpriseApiController::class, 'getDashboard']);
        Route::get('/sub-accounts', [EnterpriseApiController::class, 'getSubAccounts']);
        Route::post('/sub-accounts', [EnterpriseApiController::class, 'createSubAccount']);
        Route::put('/sub-accounts/{id}', [EnterpriseApiController::class, 'updateSubAccount']);
        Route::delete('/sub-accounts/{id}', [EnterpriseApiController::class, 'deleteSubAccount']);
        Route::post('/sub-accounts/{id}/toggle', [EnterpriseApiController::class, 'toggleStatus']);
    });
    
    // Firebase Cloud Messaging (FCM) Tokens - Push Notifications
    Route::post('/fcm-token', [FcmTokenController::class, 'store']);
    Route::delete('/fcm-token', [FcmTokenController::class, 'destroy']);
    Route::get('/fcm-token', [FcmTokenController::class, 'show']);
    
    // Coupons - Codes promo pour abonnements
    Route::prefix('coupons')->group(function () {
        Route::post('/validate', [CouponApiController::class, 'validateCoupon']);
        Route::post('/mark-used', [CouponApiController::class, 'markCouponAsUsed']);
        Route::get('/my-history', [CouponApiController::class, 'getMyUsedCoupons']);
        Route::get('/available', [CouponApiController::class, 'getAvailableCoupons']);
    });
});