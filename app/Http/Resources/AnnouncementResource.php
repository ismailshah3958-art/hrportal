<?php

namespace App\Http\Resources;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Announcement */
class AnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $body = (string) $this->body;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $body,
            'excerpt' => mb_strlen($body) > 220 ? mb_substr($body, 0, 220).'…' : $body,
            'target_audience' => $this->target_audience,
            'published_at' => $this->published_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_pinned' => (bool) $this->is_pinned,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
