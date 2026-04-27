<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $rows = $user->notifications()->latest()->take(40)->get();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'data' => $rows->map(function ($n) {
                /** @var array<string, mixed> $data */
                $data = is_array($n->data) ? $n->data : [];

                return [
                    'id' => $n->id,
                    'read_at' => $n->read_at?->toIso8601String(),
                    'created_at' => $n->created_at?->toIso8601String(),
                    'class' => class_basename($n->type),
                    'title' => (string) ($data['title'] ?? 'Notification'),
                    'body' => (string) ($data['body'] ?? ''),
                    'link' => isset($data['link']) ? (string) $data['link'] : null,
                    'leave_request_id' => $data['leave_request_id'] ?? null,
                ];
            }),
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        $request->user()->notifications()->whereKey($id)->update(['read_at' => now()]);

        return response()->json(null, 204);
    }
}
