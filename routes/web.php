<?php

use App\Enums\RoleType;
use App\Http\Controllers\Api\AuthController as BackendAuthController;
use App\Http\Controllers\Api\DigitalAssetController;
use App\Http\Controllers\Frontend\Admin\BookPageController;
use App\Http\Controllers\Frontend\Admin\ChangePasswordController;
use App\Http\Controllers\Frontend\Admin\DashboardController;
use App\Http\Controllers\Frontend\Admin\LibraryCardPageController;
use App\Http\Controllers\Frontend\Admin\LibraryClassificationPageController;
use App\Http\Controllers\Frontend\Admin\LibrarySettingsPageController;
use App\Http\Controllers\Frontend\Admin\LoanPageController;
use App\Http\Controllers\Frontend\Admin\NewsPageController;
use App\Http\Controllers\Frontend\Admin\ProfileController;
use App\Http\Controllers\Frontend\Admin\UserController;
use App\Http\Controllers\Frontend\Admin\WarehousePageController;
use App\Http\Controllers\Frontend\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Frontend\Auth\NewPasswordController;
use App\Http\Controllers\Frontend\Auth\PasswordResetLinkController;
use App\Http\Controllers\Frontend\Auth\RegisteredUserController;
use App\Http\Controllers\Frontend\Auth\SocialAuthController;
use App\Http\Controllers\Frontend\Reader\ReaderPageController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', [ReaderPageController::class, 'home'])->name('reader.home');
Route::get('/gioi-thieu', [ReaderPageController::class, 'about'])->name('reader.about');
Route::redirect('/quy-dinh-thu-vien', '/quy-dinh/muon-sach', 301);
Route::prefix('quy-dinh')->group(function () {
    Route::get('/', [ReaderPageController::class, 'regulationsIndex'])->name('reader.regulations.index');
    Route::get('/thu-tuc-lam-the', [ReaderPageController::class, 'regulationsCardProcedure'])->name('reader.regulations.card');
    Route::get('/lich-phuc-vu', [ReaderPageController::class, 'regulationsSchedule'])->name('reader.regulations.schedule');
    Route::get('/muon-sach', [ReaderPageController::class, 'regulationsBorrowing'])->name('reader.regulations.borrowing');
});
Route::get('/tra-cuu-sach', [ReaderPageController::class, 'catalog'])->name('reader.catalog');
Route::get('/tra-cuu-sach/{book}/tai-lieu/{digital_asset}/xem-truoc', [ReaderPageController::class, 'catalogDigitalPreviewShow'])
    ->name('reader.catalog.digital-preview');
Route::get('/tra-cuu-sach/{book}/tai-lieu/{digital_asset}/xem-truoc/trang/{page}.png', [ReaderPageController::class, 'catalogDigitalPreviewPageImage'])
    ->whereNumber('page')
    ->name('reader.catalog.digital-preview-page-image');
Route::get('/tra-cuu-sach/{book}/tai-lieu/{digital_asset}/tai-pdf', [ReaderPageController::class, 'catalogDigitalDownloadPdf'])
    ->middleware('auth')
    ->name('reader.catalog.digital-download-pdf');
Route::get('/tra-cuu-sach/{book}', [ReaderPageController::class, 'catalogShow'])->name('reader.catalog.show');
Route::get('/tin-tuc', [ReaderPageController::class, 'newsIndex'])->name('reader.news.index');
Route::get('/tin-tuc/{slug}', [ReaderPageController::class, 'newsShow'])->name('reader.news.show');
Route::get('/dich-vu', [ReaderPageController::class, 'services'])->name('reader.services');
Route::prefix('dich-vu')->name('reader.services.')->group(function () {
    Route::get('/cap-the-thu-vien', [ReaderPageController::class, 'servicesLibraryCard'])->name('library-card');
    Route::get('/phieu-muon', [ReaderPageController::class, 'servicesLoanRequests'])->middleware('auth')->name('loan-requests');
    Route::get('/tai-lieu-so', [ReaderPageController::class, 'servicesDigitalDocuments'])->name('digital-documents');
    Route::redirect('/gio-muon', '/dich-vu/gio-sach', 301)->middleware('auth');
    Route::redirect('/gio-tai-lieu-so', '/dich-vu/gio-sach?tab=purchase', 301)->middleware('auth');
    Route::get('/gio-sach', [ReaderPageController::class, 'servicesBookCart'])->middleware('auth')->name('book-cart');
    Route::get('/thanh-toan', [ReaderPageController::class, 'servicesDigitalPayment'])->middleware('auth')->name('digital-payment');
    Route::get('/don-hang-cua-toi', [ReaderPageController::class, 'servicesDigitalOrders'])->middleware('auth')->name('digital-orders');
    Route::get('/phieu-muon/{loan}', [ReaderPageController::class, 'servicesLoanRequestShow'])->middleware('auth')->name('loan-requests.show');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [BackendAuthController::class, 'login']);
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::get('verify-otp', [RegisteredUserController::class, 'verifyOtpPage'])->name('verify-otp');
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::get('reset-password', [NewPasswordController::class, 'create'])->name('password.reset');
});
Route::get('/auth/microsoft', [SocialAuthController::class, 'redirectToMicrosoft'])->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [SocialAuthController::class, 'handleMicrosoftCallback']);

Route::middleware('auth')->group(function () {
    Route::get('/tai-khoan', [ReaderPageController::class, 'profile'])->name('reader.profile');
    Route::get('/tai-khoan/lich-su-yeu-cau-cap-nhat', [ReaderPageController::class, 'profileUpdateRequests'])->name('reader.profile-update-requests');
    Route::get('/tai-khoan/doi-mat-khau', [ReaderPageController::class, 'changePassword'])->name('reader.change-password');

    Route::get('/dashboard', function () {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }
        $roleValue = $user->user_type instanceof RoleType
            ? $user->user_type->value
            : ($user->user_type ?? '');
        if ($roleValue !== '' && in_array($roleValue, RoleType::staffRoles(), true)) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('reader.home');
    })->name('dashboard');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('admin')->name('admin.')->middleware('role_or_permission:'.RoleType::SUPER_ADMIN->value.'|role_prefix_'.RoleType::ADMIN->value.'|role_prefix_'.RoleType::LIBRARIAN->value)->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/update-requests', [UserController::class, 'updateRequests'])->name('users.update-requests');
        Route::get('/profile', ProfileController::class)->name('profile');
        Route::get('/change-password', ChangePasswordController::class)->name('change-password');
        Route::get('/books/printed', [BookPageController::class, 'printed'])->name('books.printed');
        Route::get('/books/textbook', [BookPageController::class, 'textbook'])->name('books.textbook');
        Route::get('/books/reference', [BookPageController::class, 'reference'])->name('books.reference');
        Route::get('/books/digital/submissions', [BookPageController::class, 'digitalSubmissions'])->name('books.digital-submissions');
        Route::get('/books/digital', [BookPageController::class, 'digital'])->name('books.digital');
        Route::get('/books/{book}/digital-assets/{digital_asset}/download', [DigitalAssetController::class, 'download'])
            ->name('books.digital-assets.download');
        Route::get('/books', [BookPageController::class, 'index'])->name('books.index');
        Route::get('/news-posts', [NewsPageController::class, 'index'])->name('news-posts.index');
        Route::get('/warehouses', [WarehousePageController::class, 'index'])->name('warehouses.index');
        Route::get('/warehouses/storage', [WarehousePageController::class, 'storage'])->name('warehouses.storage');
        Route::get('/warehouses/storage-cabinets', [WarehousePageController::class, 'storageCabinets'])->name('warehouses.storage-cabinets');
        Route::get('/library-settings', [LibrarySettingsPageController::class, 'index'])->name('library-settings.index');
        Route::get('/library-settings/pricing', [LibrarySettingsPageController::class, 'pricing'])->name('library-settings.pricing');
        Route::get('/library-settings/classifications', [LibrarySettingsPageController::class, 'classifications'])->name('library-settings.classifications');
        Route::get('/classifications', [LibraryClassificationPageController::class, 'index'])->name('classifications.index');
        Route::get('/library-cards', [LibraryCardPageController::class, 'index'])->name('library-cards.index');
        Route::get('/library-cards/requests', [LibraryCardPageController::class, 'requests'])->name('library-cards.requests');
        Route::get('/library-cards/counter', [LibraryCardPageController::class, 'counter'])->name('library-cards.counter');
        Route::get('/loans', [LoanPageController::class, 'index'])->name('loans.index');
        Route::get('/loans/renewal-requests', [LoanPageController::class, 'renewalRequests'])->name('loans.renewal-requests');
        Route::get('/loans/borrow-requests', [LoanPageController::class, 'borrowRequests'])->name('loans.borrow-requests');
        Route::get('/loans/create', [LoanPageController::class, 'create'])->name('loans.create');
        Route::get('/loans/{loan}', [LoanPageController::class, 'show'])->name('loans.show');
        Route::get('/loans/{loan}/edit', [LoanPageController::class, 'edit'])->name('loans.edit');
        Route::get('/loans/{loan}/return', [LoanPageController::class, 'returnPage'])->name('loans.return');
    });

});
