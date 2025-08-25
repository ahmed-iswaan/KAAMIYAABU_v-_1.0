<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\PaymentSlipApiController;
use App\Http\Controllers\Api\PaymentVisionApiController;
use App\Http\Controllers\Api\OtpCheckController;
use App\Http\Controllers\Api\UserDetailsController;

Route::prefix('v1')->middleware('verify.client')->group(function () {

        Route::get('/test', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'API is working fine 🚀'
        ]);
    });
    
     Route::post('/payments/upload-slip', [PaymentApiController::class, 'uploadSlip']);
     Route::post('/slip/parse', [PaymentSlipApiController::class, 'parseSlip']);
 
     Route::post('/individuals/exists',  [OtpCheckController::class, 'exists']);   // { phone }
     Route::post('/individuals/details', [OtpCheckController::class, 'details']); 

     Route::get('/users/by-id-card', [UserDetailsController::class, 'byIdCard']);
     Route::get('/users/profile-photo', [UserDetailsController::class, 'getProfilePhoto']);
     Route::get('/users/financial-summary', [UserDetailsController::class, 'getFinancialSummary']);

    //   Route::post('/payments/upload-slip', [PaymentVisionApiController::class, 'uploadSlip']);

});
