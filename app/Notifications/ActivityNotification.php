<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ActivityNotification extends Notification
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly array $payload
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => (string) ($this->payload['title'] ?? 'Activity'),
            'body' => (string) ($this->payload['body'] ?? ''),
            'link' => isset($this->payload['link']) ? (string) $this->payload['link'] : null,
            'meta' => $this->payload['meta'] ?? null,
        ];
    }
}
