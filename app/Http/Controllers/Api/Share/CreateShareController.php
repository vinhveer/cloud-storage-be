<?php

namespace App\Http\Controllers\Api\Share;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Share;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class CreateShareController extends BaseApiController
{
    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated.', 401, 'UNAUTHENTICATED');
        }

        $data = $request->only(['shareable_type', 'shareable_id', 'user_ids', 'permission']);

        $rules = [
            'shareable_type' => 'required|in:file,folder',
            'shareable_id' => 'required|integer',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|distinct',
            'permission' => 'required|in:view,download,edit',
        ];

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return $this->fail('Validation failed', 422, 'VALIDATION_FAILED', $validator->errors()->toArray());
        }

        $shareableType = $data['shareable_type'];
        $shareableId = (int) $data['shareable_id'];
        $userIds = array_values(array_unique($data['user_ids']));
        $permission = $data['permission'];

        // verify shareable exists and belongs to current user
        if ($shareableType === 'file') {
            $shareable = File::find($shareableId);
        } else {
            $shareable = Folder::find($shareableId);
        }

        if (! $shareable) {
            return $this->fail('Shareable not found', 404, 'NOT_FOUND');
        }

        if ($shareable->user_id !== $user->id) {
            return $this->fail('Forbidden: you do not own this resource', 403, 'FORBIDDEN');
        }

        // remove any ids that do not exist
        $existingUsers = User::whereIn('id', $userIds)->pluck('id')->toArray();
        $invalidRecipients = array_diff($userIds, $existingUsers);
        if (! empty($invalidRecipients)) {
            return $this->fail('Some recipients do not exist', 422, 'INVALID_RECIPIENTS', array_values($invalidRecipients));
        }

        DB::beginTransaction();
        try {
            $share = new Share();
            $share->shareable_type = $shareableType;
            $share->permission = $permission;
            $share->user_id = $user->id; // owner
            if ($shareableType === 'file') {
                $share->file_id = $shareableId;
            } else {
                $share->folder_id = $shareableId;
            }
            $share->save();

            // attach receivers into receives_shares pivot with permission
            foreach ($existingUsers as $recipientId) {
                // skip sharing to self
                if ($recipientId == $user->id) continue;
                DB::table('receives_shares')->insert([
                    'user_id' => $recipientId,
                    'share_id' => $share->id,
                    'permission' => $permission,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Failed to create share', 500, 'SERVER_ERROR', ['exception' => $e->getMessage()]);
        }

        // Build response shared_with
        $sharedWith = User::whereIn('id', $existingUsers)
            ->where('id', '!=', $user->id)
            ->get(['id', 'name'])
            ->map(function ($u) use ($permission) {
                return ['user_id' => $u->id, 'name' => $u->name, 'permission' => $permission];
            })->values();

        $payload = [
            'share' => [
                'share_id' => $share->id,
                'shareable_type' => $share->shareable_type,
                'shareable_id' => $shareableId,
                'user_id' => $share->user_id,
                'permission' => $share->permission,
                'created_at' => $share->created_at->toDateTimeString(),
                'shared_with' => $sharedWith,
            ],
        ];

        return $this->created($payload);
    }
}
