<?php

use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookTopicController;
use App\Http\Controllers\BookTopicFavoriteController;
use App\Http\Controllers\BookTopicFeedbackController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
Route::get('/topics/{topic}', [BookTopicController::class, 'show'])->name('topics.show');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/topics/{topic}/favorite', [BookTopicFavoriteController::class, 'store'])->name('topics.favorite');
    Route::post('/topics/{topic}/feedback', [BookTopicFeedbackController::class, 'store'])->name('topics.feedback.store');

    Route::middleware('can:access-admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/feedbacks', [AdminFeedbackController::class, 'index'])->name('feedbacks.index');
        Route::patch('/feedbacks/{feedback}', [AdminFeedbackController::class, 'update'])->name('feedbacks.update');
    });
});
