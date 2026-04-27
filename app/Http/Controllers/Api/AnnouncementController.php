<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AnnouncementController extends Controller
{
    /**
     * Published announcements visible to the current user (no HR permission required).
     */
    public function feed(Request $request)
    {
        $user = $request->user()->loadMissing('employee');

        $rows = Announcement::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->filter(fn (Announcement $a) => $this->matchesAudience($a, $user))
            ->values();

        return response()->json([
            'data' => AnnouncementResource::collection($rows)->resolve(),
        ]);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->can('hr.announcements.manage'), 403);

        $rows = Announcement::query()
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => AnnouncementResource::collection($rows)->resolve(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->can('hr.announcements.manage'), 403);

        $data = $this->validated($request);
        $data['created_by_user_id'] = $request->user()->id;

        if (empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $row = Announcement::query()->create($data);

        return response()->json([
            'message' => 'Announcement saved.',
            'data' => (new AnnouncementResource($row))->resolve(),
        ], 201);
    }

    public function update(Request $request, Announcement $announcement)
    {
        abort_unless($request->user()?->can('hr.announcements.manage'), 403);

        $announcement->update($this->validated($request, partial: true));

        return response()->json([
            'message' => 'Announcement updated.',
            'data' => (new AnnouncementResource($announcement->fresh()))->resolve(),
        ]);
    }

    public function destroy(Request $request, Announcement $announcement): Response
    {
        abort_unless($request->user()?->can('hr.announcements.manage'), 403);
        $announcement->delete();

        return response()->noContent();
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:200'],
            'body' => [$partial ? 'sometimes' : 'required', 'string', 'max:20000'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:published_at'],
            'is_pinned' => ['sometimes', 'boolean'],
        ];

        $data = $request->validate($rules);

        if (array_key_exists('department_ids', $data)) {
            $ids = $data['department_ids'];
            $data['target_audience'] = empty($ids)
                ? null
                : ['department_ids' => array_values(array_unique(array_map('intval', $ids)))];
            unset($data['department_ids']);
        } elseif (! $partial) {
            $data['target_audience'] = null;
        }

        return $data;
    }

    private function matchesAudience(Announcement $announcement, $user): bool
    {
        $audience = $announcement->target_audience;
        if (! is_array($audience)) {
            return true;
        }

        $ids = $audience['department_ids'] ?? null;
        if (! is_array($ids) || $ids === []) {
            return true;
        }

        $deptId = $user->employee?->department_id;
        if ($deptId === null) {
            return $user->can('hr.dashboard.view');
        }

        return in_array((int) $deptId, array_map('intval', $ids), true);
    }
}
