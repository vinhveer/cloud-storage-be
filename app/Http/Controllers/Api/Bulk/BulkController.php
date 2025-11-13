<?php

namespace App\Http\Controllers\Api\Bulk;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Bulk\BulkDeleteRequest;
use App\Http\Requests\Bulk\BulkMoveRequest;
use App\Http\Requests\Bulk\BulkCopyRequest;
use App\Services\BulkService;
use App\Exceptions\DomainValidationException;

class BulkController extends BaseApiController
{
    public function __construct(private readonly BulkService $bulkService)
    {
    }

    /**
     * POST /api/bulk/bulk-delete
     */
    public function bulkDelete(BulkDeleteRequest $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $data = $request->validated();
        $fileIds = array_key_exists('file_ids', $data) ? array_map('intval', $data['file_ids']) : [];
        $folderIds = array_key_exists('folder_ids', $data) ? array_map('intval', $data['folder_ids']) : [];

        try {
            $result = $this->bulkService->bulkDelete($user, $fileIds, $folderIds);
        } catch (DomainValidationException $e) {
            // include details if the service returned partial results
            $msg = $e->getMessage();
            return $this->fail($msg, 400, 'NO_ITEMS_TO_DELETE');
        }

        // Prepare summary and detailed info for client
        $deletedFiles = $result['files'] ?? [];
        $deletedFolders = $result['folders'] ?? [];

        $response = [
            'success' => true,
            'message' => 'Selected items moved to trash successfully.',
            'deleted' => [
                'files' => $deletedFiles,
                'folders' => $deletedFolders,
            ],
            'details' => [
                'file_result' => $result['file_result'] ?? null,
                'folder_result' => $result['folder_result'] ?? null,
            ],
        ];

        return $this->ok($response);
    }

    /**
     * POST /api/bulk/bulk-move
     */
    public function bulkMove(BulkMoveRequest $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $data = $request->validated();
        $fileIds = array_key_exists('file_ids', $data) ? array_map('intval', $data['file_ids']) : [];
        $folderIds = array_key_exists('folder_ids', $data) ? array_map('intval', $data['folder_ids']) : [];
    // allow null to represent root
    $destinationFolderId = array_key_exists('destination_folder_id', $data) ? ($data['destination_folder_id'] === null ? null : (int) $data['destination_folder_id']) : null;

        try {
            $result = $this->bulkService->bulkMove($user, $fileIds, $folderIds, $destinationFolderId);
        } catch (DomainValidationException $e) {
            return $this->fail($e->getMessage(), 400, 'DESTINATION_ERROR');
        }

        $movedFiles = $result['files'] ?? [];
        $movedFolders = $result['folders'] ?? [];

        return $this->ok([
            'success' => true,
            'message' => 'Items moved successfully.',
            'moved' => [
                'files' => $movedFiles,
                'folders' => $movedFolders,
            ],
            'destination_folder_id' => $destinationFolderId,
        ]);
    }

    /**
     * POST /api/bulk/bulk-copy
     */
    public function bulkCopy(BulkCopyRequest $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $data = $request->validated();
        $fileIds = array_key_exists('file_ids', $data) ? array_map('intval', $data['file_ids']) : [];
        $folderIds = array_key_exists('folder_ids', $data) ? array_map('intval', $data['folder_ids']) : [];
        $destinationFolderId = (int) ($data['destination_folder_id'] ?? 0);

        try {
            $result = $this->bulkService->bulkCopy($user, $fileIds, $folderIds, $destinationFolderId);
        } catch (DomainValidationException $e) {
            // forward domain error message to client for clarity (still 400)
            return $this->fail($e->getMessage(), 400, 'COPY_FAILED');
        }

        $copiedFiles = $result['files'] ?? [];
        $copiedFolders = $result['folders'] ?? [];

        // normalize keys to original_id/new_id for response
        $filesResp = array_map(fn($it) => ['original_id' => $it['original'], 'new_id' => $it['new']], $copiedFiles);
        $foldersResp = array_map(fn($it) => ['original_id' => $it['original'], 'new_id' => $it['new']], $copiedFolders);

        return $this->ok([
            'success' => true,
            'message' => 'Items copied successfully.',
            'copied' => [
                'files' => $filesResp,
                'folders' => $foldersResp,
            ],
        ]);
    }
}
