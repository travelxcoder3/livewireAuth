<?php

namespace App\Services;

use App\Models\AppNotification;

class Notify
{
    public static function toUsers(array $userIds, string $title, ?string $body=null, ?string $url=null, ?string $type=null, ?int $agencyId=null): void
    {
        $now = now();
        $rows = [];
        foreach (array_unique($userIds) as $uid) {
            $rows[] = [
                'agency_id' => $agencyId,
                'user_id'   => $uid,
                'type'      => $type,
                'title'     => $title,
                'body'      => $body,
                'url'       => $url,
                'is_read'   => false,
                'read_at'   => null,
                'created_at'=> $now,
                'updated_at'=> $now,
            ];
        }
        if ($rows) { AppNotification::insert($rows); }
    }

    public static function toUser(int $userId, string $title, ?string $body=null, ?string $url=null, ?string $type=null, ?int $agencyId=null): void
    {
        self::toUsers([$userId], $title, $body, $url, $type, $agencyId);
    }
}
