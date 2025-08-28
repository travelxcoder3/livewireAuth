<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = [
        'agency_id','user_id','type','title','body','url','is_read','read_at',
    ];

    // 🔹 forUser(scope)
    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    // 🔹 unread(scope)
    public function scopeUnread(Builder $q): Builder
    {
        return $q->where('is_read', false);
    }
}
