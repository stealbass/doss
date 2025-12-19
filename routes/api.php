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
use App\Http\Controllers\Api\FcmTokenController;

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
    // App Configuration (checked on app startup)
    Route::get('/config', [ConfigController::class, 'getConfig']);
    
    // Authentication
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Referral validation (for registration)
    Route::post('/referral/validate', [ReferralController::class, 'validateReferralCode']);
    
    // Plans (viewable without auth)
    Route::get('/plans', [SubscriptionController::class, 'getPlans']);
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
        Route::post('/conversation', [ChatController::class, 'createConversation']);
        Route::get('/conversations', [ChatController::class, 'getConversations']);
        Route::get('/conversation/{id}/messages', [ChatController::class, 'getMessages']);
        Route::post('/send', [ChatController::class, 'sendMessage']);
        Route::delete('/conversation/{id}', [ChatController::class, 'deleteConversation']);
    });
    
    // Documents
    Route::prefix('documents')->group(function () {
        // User documents (submitted)
        Route::post('/upload', [DocumentController::class, 'upload']);
        Route::get('/my-documents', [DocumentController::class, 'getUserDocuments']);
        Route::delete('/{id}', [DocumentController::class, 'deleteDocument']);
        
        // Legal library
        Route::post('/search', [DocumentController::class, 'searchLegalDocuments']);
        Route::get('/legal/{id}/download', [DocumentController::class, 'downloadLegalDocument']);
    });
    
    // Subscription
    Route::prefix('subscription')->group(function () {
        Route::get('/current', [SubscriptionController::class, 'getCurrentSubscription']);
        Route::post('/initiate', [SubscriptionController::class, 'initiateSubscription']);
        Route::post('/activate', [SubscriptionController::class, 'activateSubscription']);
        Route::post('/cancel', [SubscriptionController::class, 'cancelSubscription']);
        Route::get('/payments', [SubscriptionController::class, 'getPaymentHistory']);
    });
    
    // Referral
    Route::prefix('referral')->group(function () {
        Route::get('/code', [ReferralController::class, 'getReferralCode']);
        Route::get('/history', [ReferralController::class, 'getReferralHistory']);
        Route::get('/rewards', [ReferralController::class, 'getReferralRewards']);
    });
    
    // User's plan limits
    Route::get('/limits', [ConfigController::class, 'getPlanLimits']);
    
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
});
