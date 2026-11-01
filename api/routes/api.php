<?php

use App\Modules\Catalog\Http\Controllers\HomeController;
use App\Modules\Catalog\Http\Controllers\ProgramController;
use App\Modules\Certification\Http\Controllers\CertificateController;
use App\Modules\Commerce\Http\Controllers\OrderController;
use App\Modules\Commerce\Http\Controllers\PaymentWebhookController;
use App\Modules\Identity\Http\Controllers\AuthController;
use App\Modules\Identity\Http\Controllers\EmailVerificationController;
use App\Modules\Identity\Http\Controllers\PasswordResetController;
use App\Modules\Identity\Http\Controllers\ProfileController;
use App\Modules\Learning\Http\Controllers\DashboardController;
use App\Modules\Learning\Http\Controllers\EnrollmentController;
use App\Modules\Learning\Http\Controllers\LessonController;
use App\Modules\Learning\Http\Controllers\MentorSubmissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('health', fn () => response()->json(['ok' => true, 'time' => now()->toIso8601String()]));

    // Publik
    Route::middleware('throttle:public')->group(function () {
        Route::get('categories', [HomeController::class, 'categories']);
        Route::get('testimonials', [HomeController::class, 'testimonials']);
        Route::get('stats', [HomeController::class, 'stats']);
        Route::get('programs', [ProgramController::class, 'index']);
        Route::get('programs/{slug}', [ProgramController::class, 'show']);
        Route::get('certificates/verify/{code}', [CertificateController::class, 'verify']);
        Route::get('certificates/{code}/pdf', [CertificateController::class, 'pdf'])->name('certificates.pdf');
    });

    Route::post('webhooks/{gateway}', PaymentWebhookController::class)->name('webhooks.payment');

    // Auth
    Route::prefix('auth')->group(function () {
        Route::middleware('throttle:login')->group(function () {
            Route::post('register', [AuthController::class, 'register']);
            Route::post('login', [AuthController::class, 'login']);
            Route::post('forgot-password', [PasswordResetController::class, 'forgot']);
            Route::post('reset-password', [PasswordResetController::class, 'reset']);
        });
        Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware(['signed:relative', 'throttle:6,1'])
            ->name('verification.verify');
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::put('me', [ProfileController::class, 'update']);
        Route::post('me/cv', [ProfileController::class, 'uploadCv']);
        Route::post('auth/email/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:3,1');

        // Checkout
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::middleware('throttle:checkout')->group(function () {
            Route::post('orders', [OrderController::class, 'store']);
            Route::post('orders/{order}/vouchers', [OrderController::class, 'applyVoucher']);
            Route::delete('orders/{order}/vouchers', [OrderController::class, 'removeVoucher']);
            Route::post('orders/{order}/pay', [OrderController::class, 'pay']);
            Route::post('orders/{order}/simulate-payment', [OrderController::class, 'simulatePayment']);
        });

        // LMS
        Route::get('dashboard', DashboardController::class);
        Route::get('enrollments', [EnrollmentController::class, 'index']);
        Route::get('enrollments/{enrollment}/curriculum', [EnrollmentController::class, 'curriculum']);
        Route::get('enrollments/{enrollment}/lessons/{lesson}', [LessonController::class, 'show']);
        Route::put('enrollments/{enrollment}/lessons/{lesson}/progress', [LessonController::class, 'progress']);
        Route::post('enrollments/{enrollment}/lessons/{lesson}/submissions', [LessonController::class, 'submit']);
        Route::get('enrollments/{enrollment}/lessons/{lesson}/discussions', [LessonController::class, 'discussions']);
        Route::post('enrollments/{enrollment}/lessons/{lesson}/discussions', [LessonController::class, 'postDiscussion']);
        Route::get('enrollments/{enrollment}/live-sessions/{liveSession}/join', [LessonController::class, 'joinLive']);
        Route::get('certificates', [CertificateController::class, 'mine']);

        // Mentor
        Route::middleware('role:mentor|pm|admin')->prefix('mentor')->group(function () {
            Route::get('submissions', [MentorSubmissionController::class, 'index']);
            Route::put('submissions/{submission}/grade', [MentorSubmissionController::class, 'grade']);
        });
    });
});
