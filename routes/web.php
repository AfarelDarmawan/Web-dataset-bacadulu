<?php

use App\Http\Controllers\Admin\AccessRequestController as AdminAccessRequestController;
use App\Http\Controllers\Admin\AiExtractionController;
use App\Http\Controllers\Admin\AiIngestionController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DataProviderController;
use App\Http\Controllers\Admin\DataConnectorController;
use App\Http\Controllers\Admin\DataSyncController;
use App\Http\Controllers\Admin\DatasetController as AdminDatasetController;
use App\Http\Controllers\Admin\DatasetImportController;
use App\Http\Controllers\Admin\DatasetObservationController;
use App\Http\Controllers\Admin\DatasetVariableController;
use App\Http\Controllers\Admin\SourceDocumentController;
use App\Http\Controllers\Admin\QualityCenterController;
use App\Http\Controllers\Admin\VariableCatalogController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AdminSessionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\PublicSite\DatasetCatalogController;
use App\Http\Controllers\PublicSite\HomeController;
use App\Http\Controllers\User\DataAccessRequestController;
use App\Http\Controllers\User\DatasetDownloadController;
use App\Http\Controllers\User\ProfileController as UserProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', HomeController::class)->name('home');
Route::get('/datasets', [DatasetCatalogController::class, 'index'])->middleware('throttle:public-catalog')->name('datasets.index');
Route::get('/datasets/{dataset:slug}/variables/{variable}', [DatasetCatalogController::class, 'variable'])
    ->middleware('throttle:public-catalog')
    ->scopeBindings()
    ->name('datasets.variables.show');
Route::get('/datasets/{dataset:slug}', [DatasetCatalogController::class, 'show'])->middleware('throttle:public-catalog')->name('datasets.show');

Route::get('/media/avatar/{filename}', function (string $filename) {
    abort_unless(preg_match('/\A[A-Za-z0-9._-]+\z/', $filename) === 1, 404);

    $disk = Storage::disk('public');
    $path = 'avatars/'.$filename;

    abort_unless($disk->exists($path), 404);

    return response()->file($disk->path($path), [
        'Content-Type' => $disk->mimeType($path),
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->where('filename', '[A-Za-z0-9._-]+')->name('media.avatar');

$adminPath = trim((string) config('bacadulu.admin.path', 'panel-adminbaca'), '/');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:auth-login')->name('login.store');
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:registration')->name('register.store');

Route::prefix($adminPath)->name('admin.')->group(function (): void {
    Route::get('login', [AdminSessionController::class, 'create'])->name('login');
    Route::post('login', [AdminSessionController::class, 'store'])->middleware('throttle:auth-login')->name('login.store');
    Route::post('logout', [AdminSessionController::class, 'destroy'])->middleware('auth:admin')->name('logout');
});
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:web')->name('logout');

Route::middleware(['auth:web', 'account.active', 'researcher'])->group(function (): void {
    Route::get('/profile', UserProfileController::class)->name('user.profile');
    Route::get('/profile/edit', [UserProfileController::class, 'edit'])->name('user.profile.edit');
    Route::patch('/profile', [UserProfileController::class, 'update'])->middleware('throttle:profile-update')->name('user.profile.update');
    Route::get('/dashboard', fn () => redirect()->route('user.profile'))->name('user.dashboard');
    Route::get('/my-requests', [DataAccessRequestController::class, 'index'])->name('user.requests.index');
    Route::get('/my-requests/{accessRequest}', [DataAccessRequestController::class, 'show'])->name('user.requests.show');
    Route::post('/datasets/{dataset:slug}/request', [DataAccessRequestController::class, 'store'])->middleware('throttle:data-request')->name('datasets.request');
    Route::post('/datasets/{dataset:slug}/download', [DatasetDownloadController::class, 'open'])->middleware('throttle:data-download')->name('datasets.download.open');
    Route::post('/my-requests/{accessRequest}/download', [DatasetDownloadController::class, 'approved'])->middleware('throttle:data-download')->name('user.requests.download');
});

Route::prefix($adminPath)->name('admin.')->middleware(['auth:admin', 'account.active', 'admin'])->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('catalog/variables', VariableCatalogController::class)->name('catalog.variables.index');
    Route::get('quality', QualityCenterController::class)->name('quality.index');

    Route::get('automation', [DataConnectorController::class, 'index'])->name('automation.index');
    Route::get('automation/connectors/create', [DataConnectorController::class, 'create'])->name('automation.connectors.create');
    Route::post('automation/connectors', [DataConnectorController::class, 'store'])->middleware('throttle:admin-write')->name('automation.connectors.store');
    Route::get('automation/connectors/{connector}/edit', [DataConnectorController::class, 'edit'])->name('automation.connectors.edit');
    Route::put('automation/connectors/{connector}', [DataConnectorController::class, 'update'])->middleware('throttle:admin-write')->name('automation.connectors.update');
    Route::patch('automation/connectors/{connector}/toggle', [DataConnectorController::class, 'toggle'])->middleware('throttle:admin-write')->name('automation.connectors.toggle');
    Route::delete('automation/connectors/{connector}', [DataConnectorController::class, 'destroy'])->middleware('throttle:admin-write')->name('automation.connectors.destroy');
    Route::post('automation/connectors/{connector}/sync', [DataSyncController::class, 'store'])->middleware('throttle:data-sync')->name('automation.connectors.sync');
    Route::get('automation/connectors/{connector}/runs/{run}', [DataSyncController::class, 'show'])->name('automation.runs.show');
    Route::post('automation/connectors/{connector}/runs/{run}/bulk', [DataSyncController::class, 'bulk'])->middleware('throttle:admin-write')->name('automation.runs.bulk');
    Route::post('automation/connectors/{connector}/runs/{run}/apply', [DataSyncController::class, 'apply'])->middleware('throttle:admin-write')->name('automation.runs.apply');
    Route::post('automation/connectors/{connector}/runs/{run}/reject', [DataSyncController::class, 'reject'])->middleware('throttle:admin-write')->name('automation.runs.reject');

    Route::get('ai-ingestion', [AiIngestionController::class, 'index'])->name('ai.index');
    Route::post('source-documents', [SourceDocumentController::class, 'store'])->middleware('throttle:source-upload')->name('source-documents.store');
    Route::get('source-documents/{sourceDocument}', [SourceDocumentController::class, 'show'])->name('source-documents.show');
    Route::get('source-documents/{sourceDocument}/download', [SourceDocumentController::class, 'download'])->middleware('throttle:data-download')->name('source-documents.download');
    Route::delete('source-documents/{sourceDocument}', [SourceDocumentController::class, 'destroy'])->middleware('throttle:admin-write')->name('source-documents.destroy');
    Route::post('source-documents/{sourceDocument}/extract', [AiExtractionController::class, 'store'])->middleware('throttle:ai-extraction')->name('source-documents.extract');
    Route::get('ai-extractions/{aiExtraction}', [AiExtractionController::class, 'show'])->name('ai-extractions.show');
    Route::get('ai-extractions/{aiExtraction}/rows/{row}/edit', [AiExtractionController::class, 'editRow'])->name('ai-extractions.rows.edit');
    Route::patch('ai-extractions/{aiExtraction}/rows/{row}', [AiExtractionController::class, 'updateRow'])->middleware('throttle:admin-write')->name('ai-extractions.rows.update');
    Route::post('ai-extractions/{aiExtraction}/rows/bulk', [AiExtractionController::class, 'bulkRows'])->middleware('throttle:admin-write')->name('ai-extractions.rows.bulk');
    Route::post('ai-extractions/{aiExtraction}/approve', [AiExtractionController::class, 'approve'])->middleware('throttle:admin-write')->name('ai-extractions.approve');
    Route::post('ai-extractions/{aiExtraction}/reject', [AiExtractionController::class, 'reject'])->middleware('throttle:admin-write')->name('ai-extractions.reject');
    Route::post('ai-extractions/{aiExtraction}/recover', [AiExtractionController::class, 'recover'])->middleware('throttle:admin-write')->name('ai-extractions.recover');

    Route::resource('providers', DataProviderController::class)->only(['index', 'create', 'edit']);
    Route::post('providers', [DataProviderController::class, 'store'])->middleware('throttle:admin-write')->name('providers.store');
    Route::match(['put', 'patch'], 'providers/{provider}', [DataProviderController::class, 'update'])->middleware('throttle:admin-write')->name('providers.update');
    Route::delete('providers/{provider}', [DataProviderController::class, 'destroy'])->middleware('throttle:admin-write')->name('providers.destroy');

    Route::post('datasets/{dataset}/publish', [AdminDatasetController::class, 'publish'])->middleware('throttle:admin-write')->name('datasets.publish');
    Route::post('datasets/{dataset}/archive', [AdminDatasetController::class, 'archive'])->middleware('throttle:admin-write')->name('datasets.archive');
    Route::get('datasets/{dataset}/import', [DatasetImportController::class, 'create'])->name('datasets.import.create');
    Route::post('datasets/{dataset}/import', [DatasetImportController::class, 'store'])->middleware('throttle:dataset-import')->name('datasets.import.store');
    Route::get('datasets/{dataset}/observations', [DatasetObservationController::class, 'index'])->name('datasets.observations.index');
    Route::patch('datasets/{dataset}/observations/{observation}', [DatasetObservationController::class, 'update'])->middleware('throttle:admin-write')->name('datasets.observations.update');
    Route::delete('datasets/{dataset}/observations/{observation}', [DatasetObservationController::class, 'destroy'])->middleware('throttle:admin-write')->name('datasets.observations.destroy');
    Route::resource('datasets', AdminDatasetController::class)->only(['index', 'create', 'edit']);
    Route::post('datasets', [AdminDatasetController::class, 'store'])->middleware('throttle:admin-write')->name('datasets.store');
    Route::match(['put', 'patch'], 'datasets/{dataset}', [AdminDatasetController::class, 'update'])->middleware('throttle:admin-write')->name('datasets.update');
    Route::delete('datasets/{dataset}', [AdminDatasetController::class, 'destroy'])->middleware('throttle:admin-write')->name('datasets.destroy');

    Route::resource('datasets.variables', DatasetVariableController::class)->only(['index', 'create', 'edit']);
    Route::post('datasets/{dataset}/variables', [DatasetVariableController::class, 'store'])->middleware('throttle:admin-write')->name('datasets.variables.store');
    Route::match(['put', 'patch'], 'datasets/{dataset}/variables/{variable}', [DatasetVariableController::class, 'update'])->middleware('throttle:admin-write')->name('datasets.variables.update');
    Route::delete('datasets/{dataset}/variables/{variable}', [DatasetVariableController::class, 'destroy'])->middleware('throttle:admin-write')->name('datasets.variables.destroy');

    Route::get('requests', [AdminAccessRequestController::class, 'index'])->name('requests.index');
    Route::get('requests/{accessRequest}', [AdminAccessRequestController::class, 'show'])->name('requests.show');
    Route::patch('requests/{accessRequest}', [AdminAccessRequestController::class, 'update'])->middleware('throttle:admin-write')->name('requests.update');

    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('users/{user}/toggle', [AdminUserController::class, 'toggle'])->middleware('throttle:admin-write')->name('users.toggle');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audits.index');
});
