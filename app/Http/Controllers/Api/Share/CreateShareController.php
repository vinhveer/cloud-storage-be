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

        $shareCreated = false;
        $addedUserIds = [];
        $updatedUserIds = [];
        $skippedUserIds = [];

        DB::beginTransaction();
        try {
            // reuse existing share for same owner + shareable if present
            $shareQuery = Share::where('user_id', $user->id);
            if ($shareableType === 'file') {
                $shareQuery->where('file_id', $shareableId);
            } else {
                $shareQuery->where('folder_id', $shareableId);
            }

            $share = $shareQuery->first();

            if (! $share) {
                $share = new Share();
                $share->shareable_type = $shareableType;
                $share->user_id = $user->id; // owner
                if ($shareableType === 'file') {
                    $share->file_id = $shareableId;
                } else {
                    $share->folder_id = $shareableId;
                }
                $share->save();
                $shareCreated = true;
            }

            // prepare recipient ids excluding owner and duplicates
            $recipientIds = array_values(array_filter($existingUsers, function ($id) use ($user) {
                return $id != $user->id;
            }));

            if (! empty($recipientIds)) {
                // fetch existing pivot rows for these recipients
                $existingPivots = DB::table('receives_shares')
                    ->where('share_id', $share->id)
                    ->whereIn('user_id', $recipientIds)
                    ->pluck('permission', 'user_id')
                    ->toArray();

                $toAttach = [];
                $toUpdate = [];
                foreach ($recipientIds as $rid) {
                    if (isset($existingPivots[$rid])) {
                        // update permission on pivot if differs
                        if ($existingPivots[$rid] !== $permission) {
                            $toUpdate[$rid] = ['permission' => $permission];
                            $updatedUserIds[] = $rid;
                        } else {
                            // already exists with same permission -> skipped
                            $skippedUserIds[] = $rid;
                        }
                    } else {
                        $toAttach[$rid] = ['permission' => $permission];
                        $addedUserIds[] = $rid;
                    }
                }

                if (! empty($toAttach)) {
                    $share->receivers()->attach($toAttach);
                }

                foreach ($toUpdate as $rid => $pivot) {
                    $share->receivers()->updateExistingPivot($rid, $pivot);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Failed to create share', 500, 'SERVER_ERROR', ['exception' => $e->getMessage()]);
        }

        // Build response: fetch actual recipients and their pivot permissions
        $sharedWithRows = DB::table('receives_shares as rs')
            ->join('users as u', 'rs.user_id', '=', 'u.id')
            ->where('rs.share_id', $share->id)
            ->select('u.id as user_id', 'u.name', 'rs.permission')
            ->get();

        $sharedWith = $sharedWithRows->map(function ($r) {
            return ['user_id' => (int) $r->user_id, 'name' => $r->name, 'permission' => $r->permission];
        })->values();

        $payload = [
            'share' => [
                'share_id' => $share->id,
                'shareable_type' => $share->shareable_type,
                'shareable_id' => $shareableId,
                'user_id' => $share->user_id,
                'created_at' => $share->created_at->toDateTimeString(),
                'shared_with' => $sharedWith,
            ],
            'share_created' => $shareCreated,
            'added_user_ids' => array_values($addedUserIds),
            'updated_user_ids' => array_values($updatedUserIds),
            'skipped_user_ids' => array_values($skippedUserIds),
        ];

        return $this->created($payload);
    }
}
