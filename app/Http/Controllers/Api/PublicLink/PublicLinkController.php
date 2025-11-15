<?php

namespace App\Http\Controllers\Api\PublicLink;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\PublicLink\StorePublicLinkRequest;
use App\Http\Requests\PublicLink\UpdatePublicLinkRequest;
use App\Http\Requests\PublicLink\ListPublicLinksRequest;
use App\Services\PublicLinkService;
use App\Exceptions\DomainValidationException;

class PublicLinkController extends BaseApiController
{
    public function __construct(private readonly PublicLinkService $service) {}

    /**
     * 8.3. API: GET /api/public-links/{token}
     * Description: Lấy thông tin chi tiết public link (public)
     */
    public function showByToken(string $token)
    {
        try {
            $link = $this->service->getActiveByToken($token);
        } catch (DomainValidationException $e) {
            $lower = strtolower($e->getMessage());
            if (str_contains($lower, 'not found')) {
                return $this->fail($e->getMessage(), 404, 'PUBLIC_LINK_NOT_FOUND');
            }
            if (str_contains($lower, 'revoked') || str_contains($lower, 'expired')) {
                return $this->fail($e->getMessage(), 403, 'FORBIDDEN');
            }
            return $this->fail($e->getMessage(), 400, 'BAD_REQUEST');
        }

        $shareableName = $link->shareable_type === 'file'
            ? ($link->file?->display_name ?? null)
            : ($link->folder?->folder_name ?? null);

        return $this->ok([
            'public_link_id' => $link->id,
            'shareable_type' => $link->shareable_type,
            'shareable_name' => $shareableName,
            'permission' => $link->permission,
            'token' => $link->token,
            'expired_at' => $link->expired_at?->toIso8601String(),
            'revoked_at' => $link->revoked_at?->toIso8601String(),
            'created_at' => $link->created_at?->toIso8601String(),
            'owner' => [
                'user_id' => $link->user->id,
                'name' => $link->user->name,
            ],
        ]);
    }

    /**
     * 8.6. API: GET /api/public-links/{token}/preview
     * Description: Xem trước file qua public link (public)
     */
    public function preview(string $token)
    {
        try {
            $result = $this->service->buildPreviewUrl($token);
        } catch (DomainValidationException $e) {
            $lower = strtolower($e->getMessage());
            if (str_contains($lower, 'not for a file')) {
                return $this->fail($e->getMessage(), 400, 'BAD_REQUEST');
            }
            if (str_contains($lower, 'not found')) {
                return $this->fail($e->getMessage(), 404, 'PUBLIC_LINK_NOT_FOUND');
            }
            if (str_contains($lower, 'revoked') || str_contains($lower, 'expired') || str_contains($lower, 'does not grant')) {
                return $this->fail($e->getMessage(), 403, 'FORBIDDEN');
            }
            if (str_contains($lower, 'not supported')) {
                return $this->fail($e->getMessage(), 400, 'PREVIEW_NOT_SUPPORTED');
            }
            if (str_contains($lower, 'content not found') || str_contains($lower, 'version not found')) {
                return $this->fail($e->getMessage(), 404, 'FILE_CONTENT_NOT_FOUND');
            }
            return $this->fail($e->getMessage(), 400, 'BAD_REQUEST');
        }

        $file = $result['file'];
        return $this->ok([
            'shareable_type' => 'file',
            'file' => [
                'file_id' => $file['file_id'],
                'display_name' => $file['display_name'],
                'mime_type' => $file['mime_type'],
                'size' => (int) ($file['file_size'] ?? 0),
                'url' => $result['preview_url'],
            ],
        ]);
    }

    /**
     * 8.7. API: GET /api/public-links/{token}/download
     * Description: Tải file qua public link (public, permission = download)
     */
    public function download(string $token)
    {
        try {
            $url = $this->service->buildDownloadUrl($token);
        } catch (DomainValidationException $e) {
            $lower = strtolower($e->getMessage());
            if (str_contains($lower, 'not for a file')) {
                return $this->fail($e->getMessage(), 400, 'BAD_REQUEST');
            }
            if (str_contains($lower, 'not found')) {
                return $this->fail($e->getMessage(), 404, 'PUBLIC_LINK_NOT_FOUND');
            }
            if (str_contains($lower, 'revoked') || str_contains($lower, 'expired') || str_contains($lower, 'does not grant')) {
                return $this->fail($e->getMessage(), 403, 'FORBIDDEN');
            }
            return $this->fail($e->getMessage(), 400, 'BAD_REQUEST');
        }

        return $this->ok([
            'success' => true,
            'download_url' => $url,
        ]);
    }

    /**
     * 8.1. API: POST /api/public-links
     * Description: Tạo public link cho file hoặc folder (auth)
     */
    public function store(StorePublicLinkRequest $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $data = $request->validated();

        try {
            $link = $this->service->create(
                $user,
                $data['shareable_type'],
                (int) $data['shareable_id'],
                $data['permission'],
                $data['expired_at'] ?? null
            );
        } catch (DomainValidationException $e) {
            $lower = strtolower($e->getMessage());
            if (str_contains($lower, 'file not found')) {
                return $this->fail($e->getMessage(), 404, 'FILE_NOT_FOUND');
            }
            if (str_contains($lower, 'folder not found')) {
                return $this->fail($e->getMessage(), 404, 'FOLDER_NOT_FOUND');
            }
            if (str_contains($lower, 'not owned')) {
                return $this->fail($e->getMessage(), 403, 'FORBIDDEN');
            }
            if (str_contains($lower, 'invalid') || str_contains($lower, 'deleted')) {
                return $this->fail($e->getMessage(), 422, 'VALIDATION_ERROR');
            }
            return $this->fail($e->getMessage(), 400, 'BAD_REQUEST');
        }

        $shareableId = $link->shareable_type === 'file' ? $link->file_id : $link->folder_id;

        return $this->created([
            'message' => 'Public link created successfully.',
            'public_link' => [
                'public_link_id' => $link->id,
                'shareable_type' => $link->shareable_type,
                'shareable_id' => $shareableId,
                'permission' => $link->permission,
                'token' => $link->token,
                'url' => $this->service->buildPublicUrl($link->token),
                'expired_at' => $link->expired_at?->toIso8601String(),
                'created_at' => $link->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * 8.2. API: GET /api/public-links
     * Description: Danh sách tất cả public link của user hiện tại (auth)
     */
    public function index(ListPublicLinksRequest $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $page = (int) ($request->validated()['page'] ?? (int) $request->query('page', 1));
        $perPage = (int) ($request->validated()['per_page'] ?? (int) $request->query('per_page', 15));
        $includeRevoked = filter_var($request->query('include_revoked', false), FILTER_VALIDATE_BOOLEAN);

        $result = $this->service->listByUser($user, $page, $perPage, $includeRevoked);
        $items = $result['items'];
        $total = (int) $result['total'];
        $totalPages = (int) ceil($total / max(1, $perPage));

        return $this->ok([
            'data' => $items->map(function ($pl) {
                $shareableName = $pl->shareable_type === 'file'
                    ? ($pl->file?->display_name ?? null)
                    : ($pl->folder?->folder_name ?? null);
                return [
                    'public_link_id' => $pl->id,
                    'shareable_type' => $pl->shareable_type,
                    'shareable_name' => $shareableName,
                    'permission' => $pl->permission,
                    'token' => $pl->token,
                    'url' => $this->service->buildPublicUrl($pl->token),
                    'expired_at' => $pl->expired_at?->toIso8601String(),
                    'revoked_at' => $pl->revoked_at?->toIso8601String(),
                    'created_at' => $pl->created_at?->toIso8601String(),
                ];
            })->all(),
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
            ],
        ]);
    }

    /**
     * 8.4. API: DELETE /api/public-links/{id}
     * Description: Thu hồi public link (auth)
     */
    public function destroy(int $id)
    {
        $user = request()->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            $this->service->revoke($user, $id);
        } catch (DomainValidationException $e) {
            $lower = strtolower($e->getMessage());
            if (str_contains($lower, 'not found')) {
                return $this->fail($e->getMessage(), 404, 'PUBLIC_LINK_NOT_FOUND');
            }
            return $this->fail($e->getMessage(), 403, 'FORBIDDEN');
        }

        return $this->ok([
            'success' => true,
            'message' => 'Public link revoked successfully.',
        ]);
    }

    /**
     * 8.5. API: PUT /api/public-links/{id}
     * Description: Cập nhật quyền hoặc thời hạn hết hạn (auth)
     */
    public function update(UpdatePublicLinkRequest $request, int $id)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $data = $request->validated();
        $permission = $data['permission'] ?? null;
        $expiredAt = array_key_exists('expired_at', $data) ? $data['expired_at'] : null;

        try {
            $link = $this->service->update($user, $id, $permission, $expiredAt);
        } catch (DomainValidationException $e) {
            $lower = strtolower($e->getMessage());
            if (str_contains($lower, 'not found')) {
                return $this->fail($e->getMessage(), 404, 'PUBLIC_LINK_NOT_FOUND');
            }
            if (str_contains($lower, 'invalid') || str_contains($lower, 'date')) {
                return $this->fail($e->getMessage(), 422, 'VALIDATION_ERROR');
            }
            return $this->fail($e->getMessage(), 403, 'FORBIDDEN');
        }

        return $this->ok([
            'success' => true,
            'message' => 'Public link updated successfully.',
            'public_link' => [
                'public_link_id' => $link->id,
                'permission' => $link->permission,
                'expired_at' => $link->expired_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * 8.8. API: POST /api/public-links/{id}/revoke
     * Description: Thu hồi thủ công public link (auth)
     */
    public function revoke(int $id)
    {
        $user = request()->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            $this->service->revoke($user, $id);
        } catch (DomainValidationException $e) {
            $lower = strtolower($e->getMessage());
            if (str_contains($lower, 'not found')) {
                return $this->fail($e->getMessage(), 404, 'PUBLIC_LINK_NOT_FOUND');
            }
            return $this->fail($e->getMessage(), 403, 'FORBIDDEN');
        }

        return $this->ok([
            'success' => true,
            'message' => 'Public link manually revoked.',
        ]);
    }

    /**
     * 8.9. API: GET /api/files/{id}/public-links
     * Description: Danh sách public link theo file (auth)
     */
    public function forFile(int $id)
    {
        $user = request()->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            $result = $this->service->listForFile($user, $id);
        } catch (DomainValidationException $e) {
            $lower = strtolower($e->getMessage());
            if (str_contains($lower, 'file not found')) {
                return $this->fail($e->getMessage(), 404, 'FILE_NOT_FOUND');
            }
            return $this->fail($e->getMessage(), 403, 'FORBIDDEN');
        }

        $file = $result['file'];
        $links = $result['links'];

        return $this->ok([
            'file_id' => $file->id,
            'file_name' => $file->display_name,
            'public_links' => $links->map(function ($pl) {
                return [
                    'public_link_id' => $pl->id,
                    'permission' => $pl->permission,
                    'token' => $pl->token,
                    'url' => $this->service->buildPublicUrl($pl->token),
                    'expired_at' => $pl->expired_at?->toIso8601String(),
                    'revoked_at' => $pl->revoked_at?->toIso8601String(),
                ];
            })->all(),
        ]);
    }
}
