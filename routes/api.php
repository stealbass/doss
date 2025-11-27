<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\ChatController;
use App\Http\Controllers\Api\Mobile\DocumentController;
use App\Http\Controllers\Api\Mobile\SubscriptionController;
use App\Http\Controllers\Api\Mobile\ReferralController;

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
});
