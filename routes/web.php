<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
  Route::get('login', [\App\Http\Controllers\Frontend\Auth\AuthenticatedSessionController::class, 'create'])
    ->name('login');
  Route::post('login', [\App\Http\Controllers\Backend\AuthController::class, 'login']);

  Route::get('register', [\App\Http\Controllers\Frontend\Auth\RegisteredUserController::class, 'create'])->name('register');
  Route::post('register', [\App\Http\Controllers\Frontend\Auth\RegisteredUserController::class, 'store']);

  Route::get('verify-otp', [\App\Http\Controllers\Frontend\Auth\RegisteredUserController::class, 'verifyOtpPage'])->name('verify-otp');
  Route::post('verify-otp', [\App\Http\Controllers\Frontend\Auth\RegisteredUserController::class, 'verifyOtp']);
  Route::post('verify-otp/resend', [\App\Http\Controllers\Frontend\Auth\RegisteredUserController::class, 'resendOtp'])->name('verify-otp.resend');

  Route::get('forgot-password', [\App\Http\Controllers\Frontend\Auth\PasswordResetLinkController::class, 'create'])->name('password.request');
  Route::post('forgot-password', [\App\Http\Controllers\Frontend\Auth\PasswordResetLinkController::class, 'store'])->name('password.email');

  Route::get('reset-password', [\App\Http\Controllers\Frontend\Auth\NewPasswordController::class, 'create'])->name('password.reset');
  Route::post('reset-password', [\App\Http\Controllers\Frontend\Auth\NewPasswordController::class, 'store'])->name('password.store');
});
Route::get('/auth/microsoft', [\App\Http\Controllers\SocialAuthController::class, 'redirectToMicrosoft'])->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [\App\Http\Controllers\SocialAuthController::class, 'handleMicrosoftCallback']);

Route::get('/', function () {
  return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
  Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
  })->name('dashboard');
  Route::post('logout', [\App\Http\Controllers\Frontend\Auth\AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');
  Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
      return Inertia::render('Admin/Dashboard');
    })->name('dashboard');
    Route::get('/search', function () {
      return Inertia::render('Admin/Search/Index', [
        'filters' => request()->all('q')
      ]);
    })->name('search');
    Route::get('/books/trash', function () {
      $items = \App\Models\Book::onlyTrashed()->orderByDesc('deleted_at')->get()->map(fn ($b) => ['id' => $b->id, 'title' => $b->title, 'classification_code' => $b->classification_code, 'deleted_at' => $b->deleted_at?->toIso8601String()]);
      return response()->json(['data' => $items]);
    })->name('books.trash');
    Route::post('/books/restore/{id}', function ($id) {
      $book = \App\Models\Book::onlyTrashed()->find($id);
      if (!$book) return response()->json(['status' => 'error'], 410);
      $book->restore();
      return response()->json(['status' => 'success']);
    })->name('books.restore');
    Route::delete('/books/force/{id}', function ($id) {
      $book = \App\Models\Book::onlyTrashed()->find($id);
      if (!$book) return response()->json(['status' => 'error'], 410);
      $book->forceDelete();
      return response()->json(['status' => 'success']);
    })->name('books.force');
    Route::get('/books', function () {
      $books = \App\Models\Book::with(['authors', 'publisher', 'category'])
        ->withCount('copies')
        ->orderBy('updated_at', 'desc')
        ->get()
        ->map(function ($book) {
          return [
            'id' => $book->id,
            'title' => $book->title,
            'type' => $book->type instanceof \BackedEnum ? $book->type->value : ($book->type ?? 'book'),
            'classification_code' => $book->classification_code,
            'category_id' => $book->category_id,
            'publication_place' => $book->publication_place,
            'published_year' => $book->published_year,
            'total_pages' => $book->total_pages,
            'book_size' => $book->book_size,
            'volume_number' => $book->volume_number,
            'price' => $book->price,
            'notes' => $book->notes,
            'status' => $book->status ?? 'available',
            'quantity' => $book->copies_count ?? $book->total_copies ?? 0,
            'publisher_name' => $book->publisher?->name,
            'publisher' => $book->publisher ? ['id' => $book->publisher->id, 'name' => $book->publisher->name] : null,
            'authors' => $book->authors->map(fn ($a) => ['id' => $a->id, 'name' => $a->name]),
            'image_url' => $book->params['image_url'] ?? null,
          ];
        });
      return Inertia::render('Admin/Books/Index', [
        'books' => ['data' => $books, 'total' => $books->count(), 'current_page' => 1, 'last_page' => 1, 'per_page' => $books->count(), 'from' => 1, 'to' => $books->count()],
        'categories' => app(\App\Services\TaxonomyCacheService::class)->getCategories(),
        'publishers' => app(\App\Services\TaxonomyCacheService::class)->getPublishers(),
      ]);
    })->name('books.index');
    Route::get('/books/export', function () {
      return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\BooksExport(), 'danh_sach_sach_tai_lieu.xlsx');
    })->name('books.export');
    Route::get('/categories', function () {
      $tab = request('tab', 'category');
      return Inertia::render('Admin/Categories/Index', ['tab' => $tab]);
    })->name('categories.index');
    Route::get('/authors/trash', function () {
      $items = \App\Models\Author::onlyTrashed()->orderByDesc('deleted_at')->get(['id', 'name', 'nationality', 'deleted_at'])->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'nationality' => $a->nationality, 'deleted_at' => $a->deleted_at?->toIso8601String()]);
      return response()->json(['data' => $items]);
    })->name('authors.trash');
    Route::post('/authors/restore/{id}', function ($id) {
      $author = \App\Models\Author::onlyTrashed()->find($id);
      if (!$author) return response()->json(['status' => 'error'], 410);
      $author->restore();
      return response()->json(['status' => 'success']);
    })->name('authors.restore');
    Route::delete('/authors/force/{id}', function ($id) {
      $author = \App\Models\Author::onlyTrashed()->find($id);
      if (!$author) return response()->json(['status' => 'error'], 410);
      $author->forceDelete();
      return response()->json(['status' => 'success']);
    })->name('authors.force');
    Route::get('/authors', function () {
      return Inertia::render('Admin/Authors/Index');
    })->name('authors.index');
    Route::get('/publishers', function () {
      return Inertia::render('Admin/Publishers/Index');
    })->name('publishers.index');
    Route::get('/readers', function () {
      $readers = \App\Models\User::with('libraryCard')
        ->whereIn('user_type', ['MEMBER', 'GUEST'])
        ->get()
        ->map(fn ($u) => [
          'id' => $u->id,
          'name' => $u->name,
          'code' => $u->code,
          'card_number' => $u->libraryCard?->card_number,
          'issue_date' => $u->libraryCard?->issue_date?->format('Y-m-d'),
          'expiry_date' => $u->libraryCard?->expiry_date?->format('Y-m-d'),
          'faculty' => \Illuminate\Support\Arr::get($u->libraryCard?->metadata ?? [], 'faculty'),
          'class' => \Illuminate\Support\Arr::get($u->libraryCard?->metadata ?? [], 'class'),
          'type' => \Illuminate\Support\Arr::get($u->libraryCard?->metadata ?? [], 'type') === 'teacher' ? 'teacher' : 'student',
          'status' => $u->is_active ? 'active' : 'blocked',
          'gender' => $u->gender === 'male' ? 'Nam' : ($u->gender === 'female' ? 'Nữ' : 'Khác'),
          'email' => $u->email,
          'phone' => $u->phone,
        ]);
      return Inertia::render('Admin/Readers/Index', ['readers' => $readers]);
    })->name('readers.index');
    Route::get('/readers/export', function () {
      return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ReadersExport(), 'danh_sach_ban_doc.xlsx');
    })->name('readers.export');
    Route::get('/cards', function () {
      $readers = \App\Models\User::with('libraryCard')
        ->whereIn('user_type', ['MEMBER', 'GUEST'])
        ->get()
        ->map(fn ($u) => [
          'id' => $u->id,
          'name' => $u->name,
          'code' => $u->code,
          'card_number' => $u->libraryCard?->card_number,
          'issue_date' => $u->libraryCard?->issue_date?->format('Y-m-d'),
          'expiry_date' => $u->libraryCard?->expiry_date?->format('Y-m-d'),
          'faculty' => \Illuminate\Support\Arr::get($u->libraryCard?->metadata ?? [], 'faculty'),
          'class' => \Illuminate\Support\Arr::get($u->libraryCard?->metadata ?? [], 'class'),
          'type' => \Illuminate\Support\Arr::get($u->libraryCard?->metadata ?? [], 'type') === 'teacher' ? 'teacher' : 'student',
          'status' => $u->is_active ? 'active' : 'blocked',
          'gender' => $u->gender === 'male' ? 'Nam' : ($u->gender === 'female' ? 'Nữ' : 'Khác'),
          'email' => $u->email,
          'phone' => $u->phone,
        ]);
      return Inertia::render('Admin/Cards/Index', ['readers' => $readers]);
    })->name('cards.index');
    Route::prefix('library')->name('library.')->group(function () {
      Route::get('/slips', function () {
        return Inertia::render('Admin/Library/Slips');
      })->name('slips');
      Route::get('/liquidation', function () {
        return Inertia::render('Admin/Library/Liquidation');
      })->name('liquidation');
      Route::get('/inventory', function () {
        return Inertia::render('Admin/Library/Inventory');
      })->name('inventory');
    });

    Route::prefix('loans')->name('loans.')->group(function () {
      Route::get('/', function () {
        return Inertia::render('Admin/Loans/Index');
      })->name('index');
      Route::get('/extensions', function () {
        return Inertia::render('Admin/Loans/Extensions');
      })->name('extensions');
      Route::get('/onsite', function () {
        return Inertia::render('Admin/Loans/OnSite');
      })->name('onsite');
      Route::get('/penalties', function () {
        return Inertia::render('Admin/Loans/Penalties');
      })->name('penalties');
    });

    Route::get('/stats', function () {
      return Inertia::render('Admin/Stats/Index');
    })->name('stats.index');

    Route::get('/users', function () {
      return Inertia::render('Admin/Users/Index');
    })->name('users.index');
    // Thùng rác (session auth – tránh 401 khi gọi từ admin)
    Route::get('/users/trash', function () {
      $items = \App\Models\User::onlyTrashed()->orderByDesc('deleted_at')->get(['id', 'name', 'email', 'code', 'deleted_at'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'code' => $u->code, 'deleted_at' => $u->deleted_at?->toIso8601String()]);
      return response()->json(['data' => $items]);
    })->name('users.trash');
    Route::post('/users/restore/{id}', function ($id) {
      $user = \App\Models\User::onlyTrashed()->find($id);
      if (!$user) return response()->json(['status' => 'error'], 410);
      $user->restore();
      return response()->json(['status' => 'success']);
    })->name('users.restore');
    Route::delete('/users/force/{id}', function ($id) {
      $user = \App\Models\User::onlyTrashed()->find($id);
      if (!$user) return response()->json(['status' => 'error'], 410);
      $user->forceDelete();
      return response()->json(['status' => 'success']);
    })->name('users.force');
    Route::post('/users/toggle-status/{id}', function ($id) {
      $user = \App\Models\User::find($id);
      if (!$user) return response()->json(['status' => 'error'], 404);
      $user->is_active = !$user->is_active;
      $user->save();
      return response()->json(['status' => 'success', 'is_active' => $user->is_active]);
    })->name('users.toggle-status');
    // Xóa mềm (session auth – tránh 401 khi gọi từ admin)
    Route::delete('/users/{id}', function ($id) {
      $user = \App\Models\User::find($id);
      if (!$user) return response()->json(['status' => 'error'], 404);
      $user->delete();
      return response()->json(['status' => 'success']);
    })->name('users.destroy');

    Route::get('/profile', function () {
      return Inertia::render('Admin/Profile');
    })->name('profile');

    Route::prefix('settings')->name('settings.')->group(function () {
      Route::get('/', function () {
        return redirect()->route('admin.settings.rules');
      })->name('index');
      Route::get('/rules', function () {
        return Inertia::render('Admin/Settings/LoanRules');
      })->name('rules');
      Route::get('/content', function () {
        return Inertia::render('Admin/Settings/Content');
      })->name('content');
      Route::get('/appearance', function () {
        return Inertia::render('Admin/Settings/Appearance');
      })->name('appearance');
    });
  });
});
