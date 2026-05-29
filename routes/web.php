<?php

use App\Http\Controllers\AdminSubmissionController;
use App\Models\Submission;
use App\Http\Controllers\AHPController;
use App\Http\Controllers\AlternativeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\COCOSOController;
use App\Http\Controllers\CriteriaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KuesionerGroupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserSubmissionController;

Route::get('/', function (\App\Http\Controllers\COCOSOController $cocosoController) {
    if (auth()->check()) {
        return auth()->user()->isAdmin() ? redirect()->route('admin.dashboard') : redirect()->route('user.dashboard');
    }

    return $cocosoController->ranking();
});


// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Admin Routes (Protected)
Route::middleware(['auth', 'role:admin'])->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/reset', [DashboardController::class, 'reset'])->name('dashboard.reset');

    Route::prefix('criteria')->name('criteria.')->group(function () {
        Route::get('/', [CriteriaController::class, 'index'])->name('index');
        Route::post('/', [CriteriaController::class, 'store'])->name('store');
        Route::put('/{id}', [CriteriaController::class, 'update'])->name('update');
        Route::delete('/{id}', [CriteriaController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('alternative')->name('alternative.')->group(function () {
        Route::get('/', [AlternativeController::class, 'index'])->name('index');
        Route::post('/', [AlternativeController::class, 'store'])->name('store');
        Route::put('/{id}', [AlternativeController::class, 'update'])->name('update');
        Route::delete('/{id}', [AlternativeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('ahp')->name('ahp.')->group(function () {
        Route::get('/', [AHPController::class, 'index'])->name('index');
        Route::post('/calculate', [AHPController::class, 'calculate'])->name('calculate');
        Route::post('/combined-calculate', [AHPController::class, 'combinedCalculate'])->name('combined-calculate');
    });

    Route::prefix('cocoso')->name('cocoso.')->group(function () {
        Route::get('/', [COCOSOController::class, 'index'])->name('index');
        Route::post('/calculate', [COCOSOController::class, 'calculate'])->name('calculate');
    });

    Route::get('/ranking', [COCOSOController::class, 'ranking'])->name('ranking');

    // Submission Management
    Route::prefix('submissions')->name('submissions.')->group(function () {
        Route::get('/', [AdminSubmissionController::class, 'index'])->name('index');
        Route::get('/{id}', [AdminSubmissionController::class, 'show'])->name('show');
        Route::get('/{id}/input-result', [AdminSubmissionController::class, 'inputResult'])->name('input_result');
        Route::post('/{id}/store-result', [AdminSubmissionController::class, 'storeResult'])->name('store_result');
        Route::post('/{id}/auto-process', [AdminSubmissionController::class, 'autoProcess'])->name('auto_process');
        Route::delete('/{id}', [AdminSubmissionController::class, 'destroy'])->name('destroy');
    });
});

// Kuesioner Routes (Public)
Route::get('/kuisioner', function () {
    return view('kuisioner');
})->name('kuisioner');

Route::get('/kuesioner/create', [KuesionerGroupController::class, 'create'])->name('kuisioner.create');
Route::post('/kuesioner', [KuesionerGroupController::class, 'store'])->name('kuisioner.store');
Route::get('/kuesioner/{id}/form', [KuesionerGroupController::class, 'showForm'])->name('kuisioner.form');
Route::post('/kuesioner/{id}/submit', [KuesionerGroupController::class, 'submitJawaban'])->name('kuisioner.submit');
Route::delete('/admin/kuesioner/delete-terpilih', [KuesionerGroupController::class, 'destroyTerpilih'])
    ->name('admin.kuesioner.destroyTerpilih');
Route::get('/admin/kuesioner/hasil', [KuesionerGroupController::class, 'tampilkanHasil'])
    ->name('admin.kuesioner.hasil');


// Admin Kuesioner Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin/kuesioner')->name('admin.kuesioner.')->group(function () {
    Route::get('/dashboard', [KuesionerGroupController::class, 'adminDashboard'])->name('dashboard');
    Route::get('/{id}', [KuesionerGroupController::class, 'adminShow'])->name('show');
    Route::post('/{id}/eksekusi', [KuesionerGroupController::class, 'eksekusi'])->name('eksekusi');
    Route::post('/eksekusi-terpilih', [KuesionerGroupController::class, 'eksekusiTerpilih'])->name('eksekusi_terpilih');
});

// User Routes (Protected)
Route::middleware(['auth', 'role:user'])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [UserSubmissionController::class, 'index'])->name('dashboard');
    Route::get('/submission/create', [UserSubmissionController::class, 'create'])->name('submission.create');
    Route::post('/submission', [UserSubmissionController::class, 'store'])->name('submission.store');

    // New dynamic steps
    Route::get('/submission/{id}/manage-data', [UserSubmissionController::class, 'manageData'])->name('submission.manage_data');
    Route::post('/submission/{id}/criteria', [UserSubmissionController::class, 'storeCriteria'])->name('submission.store_criteria');
    Route::delete('/submission/criteria/{id}', [UserSubmissionController::class, 'destroyCriteria'])->name('submission.destroy_criteria');
    Route::post('/submission/{id}/alternative', [UserSubmissionController::class, 'storeAlternative'])->name('submission.store_alternative');
    Route::delete('/submission/alternative/{id}', [UserSubmissionController::class, 'destroyAlternative'])->name('submission.destroy_alternative');

    Route::get('/submission/{id}/input-values', [UserSubmissionController::class, 'inputValues'])->name('submission.input_values');
    Route::post('/submission/{id}/submit', [UserSubmissionController::class, 'submitValues'])->name('submission.submit_values');

    Route::get('/submission/{id}', [UserSubmissionController::class, 'show'])->name('submission.show');
    Route::get('/submission/{id}/resend', [UserSubmissionController::class, 'resend'])->name('submission.resend');
    Route::delete('/submission/{id}', [UserSubmissionController::class, 'destroy'])->name('submission.destroy');

    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    Route::get ('/kuisioner', function () {
        return view('kuisioner');
    })->name('kuisioner');

});
