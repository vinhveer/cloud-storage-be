<?php

namespace App\Http\Controllers\Api\Folder;

use App\Http\Controllers\Api\BaseApiController;
use App\Support\Traits\WithDevTrace;
use App\Http\Requests\StoreFolderRequest;
use App\Services\FolderService;
use Illuminate\Support\Facades\Auth;

class FolderController extends BaseApiController
{
    use WithDevTrace;
    public function __construct(private readonly FolderService $folderService)
    {
    }

    public function store(StoreFolderRequest $request)
    {
        $user = Auth::user();
        $folder = $this->folderService->createFolder(
            $user,
            $request->string('folder_name')->toString(),
            $request->input('parent_folder_id') !== null ? (int) $request->input('parent_folder_id') : null
        );

        return $this->ok([
            'message' => 'Folder created successfully.',
            'folder' => [
                'folder_id' => $folder->id,
                'folder_name' => $folder->folder_name,
                'fol_folder_id' => $folder->fol_folder_id,
                'user_id' => $folder->user_id,
                'created_at' => $folder->created_at?->toISOString(),
            ],
        ]);
    }
}



