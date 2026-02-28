<?php

use App\Http\Controllers\Frontend\Admin\AuthorController;
use App\Http\Controllers\Frontend\Admin\BookController;
use App\Http\Controllers\Frontend\Admin\CardController;
use App\Http\Controllers\Frontend\Admin\CategoryController;
use App\Http\Controllers\Frontend\Admin\DashboardController;
use App\Http\Controllers\Frontend\Admin\LibraryController;
use App\Http\Controllers\Frontend\Admin\LoanController;
use App\Http\Controllers\Frontend\Admin\ProfileController;
use App\Http\Controllers\Frontend\Admin\PublisherController;
use App\Http\Controllers\Frontend\Admin\ReaderController;
use App\Http\Controllers\Frontend\Admin\SearchController;
use App\Http\Controllers\Frontend\Admin\SettingsController;
use App\Http\Controllers\Frontend\Admin\StatsController;
use App\Http\Controllers\Frontend\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/trash', [BookController::class, 'trash'])->name('books.trash');
    Route::post('/books/restore/{id}', [BookController::class, 'restore'])->name('books.restore');
    Route::delete('/books/force/{id}', [BookController::class, 'forceDelete'])->name('books.force');
    Route::get('/books/export', [BookController::class, 'export'])->name('books.export');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

    Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');
    Route::get('/authors/trash', [AuthorController::class, 'trash'])->name('authors.trash');
    Route::post('/authors/restore/{id}', [AuthorController::class, 'restore'])->name('authors.restore');
    Route::delete('/authors/force/{id}', [AuthorController::class, 'forceDelete'])->name('authors.force');

    Route::get('/publishers', [PublisherController::class, 'index'])->name('publishers.index');

    Route::get('/readers', [ReaderController::class, 'index'])->name('readers.index');
    Route::get('/readers/export', [ReaderController::class, 'export'])->name('readers.export');

    Route::get('/cards', [CardController::class, 'index'])->name('cards.index');

    Route::prefix('library')->name('library.')->group(function () {
        Route::get('/slips', [LibraryController::class, 'slips'])->name('slips');
        Route::get('/liquidation', [LibraryController::class, 'liquidation'])->name('liquidation');
        Route::get('/inventory', [LibraryController::class, 'inventory'])->name('inventory');
    });

    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/', [LoanController::class, 'index'])->name('index');
        Route::get('/extensions', [LoanController::class, 'extensions'])->name('extensions');
        Route::get('/onsite', [LoanController::class, 'onsite'])->name('onsite');
        Route::get('/penalties', [LoanController::class, 'penalties'])->name('penalties');
    });

    Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/trash', [UserController::class, 'trash'])->name('users.trash');
    Route::post('/users/restore/{id}', [UserController::class, 'restore'])->name('users.restore');
    Route::delete('/users/force/{id}', [UserController::class, 'forceDelete'])->name('users.force');
    Route::post('/users/toggle-status/{id}', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/profile', ProfileController::class)->name('profile');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/rules', [SettingsController::class, 'rules'])->name('rules');
        Route::get('/content', [SettingsController::class, 'content'])->name('content');
        Route::get('/appearance', [SettingsController::class, 'appearance'])->name('appearance');
    });
});
