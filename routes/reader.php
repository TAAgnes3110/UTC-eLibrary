<?php

use App\Http\Controllers\Frontend\Reader\BookController;
use App\Http\Controllers\Frontend\Reader\CardController;
use App\Http\Controllers\Frontend\Reader\DashboardController;
use App\Http\Controllers\Frontend\Reader\LoanController;
use App\Http\Controllers\Frontend\Reader\PageController;
use Illuminate\Support\Facades\Route;

Route::prefix('library')->name('library.')->group(function () {
    Route::get('/', [BookController::class, 'search'])->name('search');
    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
    Route::get('/saved', [PageController::class, 'saved'])->name('saved')->middleware('auth');
    Route::get('/intro', [PageController::class, 'intro'])->name('intro');
    Route::get('/rules', [PageController::class, 'rules'])->name('rules');

    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/card', CardController::class)->name('card');
        Route::get('/loans', LoanController::class)->name('loans');
    });
});
