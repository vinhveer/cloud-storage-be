<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', \App\Http\Controllers\Api\Health\HealthController::class);

// Auth
Route::post('/register', [\App\Http\Controllers\Api\Auth\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\Api\Auth\AuthController::class, 'login']);
Route::post('/forgot-password', [\App\Http\Controllers\Api\Password\PasswordController::class, 'forgot']);
Route::post('/reset-password', [\App\Http\Controllers\Api\Password\PasswordController::class, 'reset']);
Route::post('/email/verify/{id}', [\App\Http\Controllers\Api\EmailVerification\EmailVerificationController::class, 'verify']);
Route::post('/email/resend', [\App\Http\Controllers\Api\EmailVerification\EmailVerificationController::class, 'resend']);

Route::middleware('auth:sanctum')->group(function () {
    // Session
    Route::post('/auth/logout', [\App\Http\Controllers\Api\Auth\AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [\App\Http\Controllers\Api\Auth\AuthController::class, 'logoutAll']);
    Route::get('/auth/me', [\App\Http\Controllers\Api\Auth\AuthController::class, 'me']);

    // User profile
    Route::get('/user', [\App\Http\Controllers\Api\User\UserController::class, 'show']);
    Route::put('/user/profile', [\App\Http\Controllers\Api\User\UserController::class, 'updateProfile']);
    Route::put('/user/password', [\App\Http\Controllers\Api\User\UserController::class, 'updatePassword']);

    // Files
    Route::post('/files', [\App\Http\Controllers\Api\File\FileController::class, 'store']);
    Route::get('/files', [\App\Http\Controllers\Api\File\FileController::class, 'index']);
    Route::get('/files/recent', [\App\Http\Controllers\Api\File\FileController::class, 'recent']);
    Route::get('/files/shared-with-me', [\App\Http\Controllers\Api\File\FileController::class, 'sharedWithMe']);
    Route::get('/files/shared-by-me', [\App\Http\Controllers\Api\File\FileController::class, 'sharedByMe']);
    Route::put('/files/{id}', [\App\Http\Controllers\Api\File\FileController::class, 'update']);
    Route::delete('/files/{id}', [\App\Http\Controllers\Api\File\FileController::class, 'destroy']);
    
    Route::post('/files/{id}/copy', [\App\Http\Controllers\Api\File\FileController::class, 'copy']);
    Route::post('/files/{id}/move', [\App\Http\Controllers\Api\File\FileController::class, 'move']);

    // File versions
    Route::post('/files/{id}/versions', [\App\Http\Controllers\Api\File\FileVersionController::class, 'store']);
    Route::get('/files/{id}/versions', [\App\Http\Controllers\Api\File\FileVersionController::class, 'index']);
    Route::get('/files/{id}/versions/{versionId}', [\App\Http\Controllers\Api\File\FileVersionController::class, 'show']);
    Route::get('/files/{id}/versions/{versionId}/download', [\App\Http\Controllers\Api\File\FileVersionController::class, 'download']);
    Route::post('/files/{id}/versions/{versionId}/restore', [\App\Http\Controllers\Api\File\FileVersionController::class, 'restore']);
    Route::delete('/files/{id}/versions/{versionId}', [\App\Http\Controllers\Api\File\FileVersionController::class, 'destroy']);

    // Trash
    Route::get('/trash', [\App\Http\Controllers\Api\Trash\CombinedTrashController::class, 'index']);
    Route::get('/trash/folders/{id}/contents', [\App\Http\Controllers\Api\Trash\CombinedTrashController::class, 'folderContents']);
    Route::get('/trash/files', [\App\Http\Controllers\Api\Trash\TrashController::class, 'files']);
    Route::get('/trash/folders', [\App\Http\Controllers\Api\Trash\TrashController::class, 'folders']);
    Route::post('/trash/{id}/restore', [\App\Http\Controllers\Api\Trash\RestoreTrashController::class, 'restore']);
    // specific route for emptying trash must come before the generic /trash/{id} route
    Route::delete('/trash/empty', [\App\Http\Controllers\Api\Trash\EmptyTrashController::class, 'emptyTrash']);
    Route::delete('/trash/{id}', [\App\Http\Controllers\Api\Trash\DeleteTrashController::class, 'destroy']);

    // Folders
    Route::post('/folders', [\App\Http\Controllers\Api\Folder\FolderController::class, 'store']);
    Route::get('/folders', [\App\Http\Controllers\Api\Folder\FolderController::class, 'index']);
    Route::get('/folders/tree', [\App\Http\Controllers\Api\Folder\FolderController::class, 'tree']);
    Route::put('/folders/{id}', [\App\Http\Controllers\Api\Folder\FolderController::class, 'update']);
    Route::delete('/folders/{id}', [\App\Http\Controllers\Api\Folder\FolderController::class, 'destroy']);
    
    Route::post('/folders/{id}/copy', [\App\Http\Controllers\Api\Folder\FolderController::class, 'copy']);
    Route::post('/folders/{id}/move', [\App\Http\Controllers\Api\Folder\FolderController::class, 'move']);

    // Shares
    Route::post('/shares', [\App\Http\Controllers\Api\Share\ShareController::class, 'store']);
    Route::get('/shares', [\App\Http\Controllers\Api\Share\ShareController::class, 'index']);
    Route::get('/shares/received', [\App\Http\Controllers\Api\Share\ShareController::class, 'received']);
    Route::get('/shares/{id}', [\App\Http\Controllers\Api\Share\ShareController::class, 'show']);
    Route::put('/shares/{id}', [\App\Http\Controllers\Api\Share\ShareController::class, 'update']);
    Route::delete('/shares/{id}', [\App\Http\Controllers\Api\Share\ShareController::class, 'destroy']);
    Route::post('/shares/{id}/users', [\App\Http\Controllers\Api\Share\ShareController::class, 'addUsers']);
    Route::delete('/shares/{id}/users/{userId}', [\App\Http\Controllers\Api\Share\ShareController::class, 'removeUser']);
    Route::put('/shares/{id}/users/{userId}', [\App\Http\Controllers\Api\Share\ShareController::class, 'updateUserPermission']);

    // Storage
    Route::get('/storage/usage', [\App\Http\Controllers\Api\Storage\StorageUsageController::class, 'usage']);
    Route::get('/storage/breakdown', [\App\Http\Controllers\Api\Storage\StorageBreakdownController::class, 'breakdown']);
    Route::get('/storage/limit', [\App\Http\Controllers\Api\Storage\StorageLimitController::class, 'limit']);

    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Api\Dashboard\DashboardController::class, 'overview']);
    Route::get('/dashboard/recent', [\App\Http\Controllers\Api\Dashboard\DashboardController::class, 'recent']);
    Route::get('/dashboard/stats', [\App\Http\Controllers\Api\Dashboard\DashboardController::class, 'stats']);

    // Bulk files
    Route::post('/files/bulk-delete', [\App\Http\Controllers\Api\File\FileBulkController::class, 'bulkDelete']);
    Route::post('/files/bulk-move', [\App\Http\Controllers\Api\File\FileBulkController::class, 'bulkMove']);
    Route::post('/files/bulk-copy', [\App\Http\Controllers\Api\File\FileBulkController::class, 'bulkCopy']);
    Route::post('/files/bulk-share', [\App\Http\Controllers\Api\File\FileBulkController::class, 'bulkShare']);
    Route::post('/files/bulk-download', [\App\Http\Controllers\Api\File\FileBulkController::class, 'bulkDownload']);
});

// Public links (no auth)
Route::get('/public-links/{token}', [\App\Http\Controllers\Api\PublicLink\PublicLinkController::class, 'showByToken']);
Route::get('/public-links/{token}/preview', [\App\Http\Controllers\Api\PublicLink\PublicLinkController::class, 'preview']);
Route::get('/public-links/{token}/download', [\App\Http\Controllers\Api\PublicLink\PublicLinkController::class, 'download']);

// Read-only routes that accept either auth:sanctum OR public link token
Route::get('/folders/{id}/breadcrumb', [\App\Http\Controllers\Api\Folder\FolderController::class, 'breadcrumb'])
    ->middleware(\App\Http\Middleware\AuthOrPublicLink::class);

Route::get('/folders/{id}/contents', [\App\Http\Controllers\Api\Folder\FolderController::class, 'contents'])
    ->middleware(\App\Http\Middleware\AuthOrPublicLink::class);

Route::get('/folders/{id}', [\App\Http\Controllers\Api\Folder\FolderController::class, 'show'])
    ->middleware(\App\Http\Middleware\AuthOrPublicLink::class);

Route::get('/files/{id}', [\App\Http\Controllers\Api\File\FileController::class, 'show'])
    ->middleware(\App\Http\Middleware\AuthOrPublicLink::class);

Route::get('/files/{id}/download', [\App\Http\Controllers\Api\File\FileController::class, 'download'])
    ->middleware(\App\Http\Middleware\AuthOrPublicLink::class);

Route::middleware(['auth:sanctum'])->group(function () {
    // Auth-required public link management
    Route::post('/public-links', [\App\Http\Controllers\Api\PublicLink\PublicLinkController::class, 'store']);
    Route::get('/public-links', [\App\Http\Controllers\Api\PublicLink\PublicLinkController::class, 'index']);
    Route::delete('/public-links/{id}', [\App\Http\Controllers\Api\PublicLink\PublicLinkController::class, 'destroy']);
    Route::put('/public-links/{id}', [\App\Http\Controllers\Api\PublicLink\PublicLinkController::class, 'update']);
    Route::post('/public-links/{id}/revoke', [\App\Http\Controllers\Api\PublicLink\PublicLinkController::class, 'revoke']);
    Route::get('/files/{id}/public-links', [\App\Http\Controllers\Api\PublicLink\PublicLinkController::class, 'forFile']);
});

// Search (auth required)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/search', [\App\Http\Controllers\Api\Search\SearchController::class, 'search']);
    Route::get('/search/files', [\App\Http\Controllers\Api\Search\SearchController::class, 'files']);
    Route::get('/search/folders', [\App\Http\Controllers\Api\Search\SearchController::class, 'folders']);
    Route::get('/search/suggestions', [\App\Http\Controllers\Api\Search\SearchController::class, 'suggestions']);
});

// Admin group (auth + admin middleware placeholder)
Route::middleware(['auth:sanctum', 'can:admin']) // TODO: replace with actual admin gate
    ->prefix('admin')
    ->group(function () {
        Route::get('/users', [\App\Http\Controllers\Api\Admin\AdminUserController::class, 'index']);
        Route::get('/users/{id}', [\App\Http\Controllers\Api\Admin\AdminUserController::class, 'show']);
        Route::post('/users', [\App\Http\Controllers\Api\Admin\AdminUserController::class, 'store']);
        Route::put('/users/{id}', [\App\Http\Controllers\Api\Admin\AdminUserController::class, 'update']);
        Route::delete('/users/{id}', [\App\Http\Controllers\Api\Admin\AdminUserController::class, 'destroy']);
        Route::put('/users/{id}/storage-limit', [\App\Http\Controllers\Api\Admin\AdminUserController::class, 'updateStorageLimit']);
        Route::get('/users/{id}/storage-usage', [\App\Http\Controllers\Api\Admin\AdminUserController::class, 'storageUsage']);
        Route::put('/users/{id}/role', [\App\Http\Controllers\Api\Admin\AdminUserController::class, 'updateRole']);

        // Storage
        Route::get('/storage/overview', [\App\Http\Controllers\Api\Admin\AdminStorageController::class, 'overview']);
        Route::get('/storage/users', [\App\Http\Controllers\Api\Admin\AdminStorageController::class, 'users']);

        // Configs
        Route::get('/configs', [\App\Http\Controllers\Api\Admin\AdminConfigController::class, 'index']);
        Route::get('/configs/{key}', [\App\Http\Controllers\Api\Admin\AdminConfigController::class, 'show']);
        Route::put('/configs/{key}', [\App\Http\Controllers\Api\Admin\AdminConfigController::class, 'update']);
        Route::post('/configs', [\App\Http\Controllers\Api\Admin\AdminConfigController::class, 'store']);
        Route::delete('/configs/{key}', [\App\Http\Controllers\Api\Admin\AdminConfigController::class, 'destroy']);

        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Api\Admin\AdminDashboardController::class, 'overview']);
        Route::get('/stats/users', [\App\Http\Controllers\Api\Admin\AdminDashboardController::class, 'users']);
        Route::get('/stats/files', [\App\Http\Controllers\Api\Admin\AdminDashboardController::class, 'files']);
        Route::get('/stats/storage', [\App\Http\Controllers\Api\Admin\AdminDashboardController::class, 'storage']);
        Route::get('/stats/activity', [\App\Http\Controllers\Api\Admin\AdminDashboardController::class, 'activity']);
    });

// Auth (Bearer token via Sanctum Personal Access Tokens)
Route::post('/auth/register', [\App\Http\Controllers\Api\Auth\AuthController::class, 'register']);
Route::post('/auth/login', [\App\Http\Controllers\Api\Auth\AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [\App\Http\Controllers\Api\Auth\AuthController::class, 'me']);
    Route::post('/auth/logout', [\App\Http\Controllers\Api\Auth\AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [\App\Http\Controllers\Api\Auth\AuthController::class, 'logoutAll']);
});


