<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\PaymentSlipApiController;

Route::prefix('v1')->middleware('verify.client')->group(function () {

        Route::get('/test', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'API is working fine 🚀'
        ]);
    });
    
    Route::post('/payments/upload-slip', [PaymentApiController::class, 'uploadSlip']);
     Route::post('/slip/parse', [PaymentSlipApiController::class, 'parseSlip']);

});
