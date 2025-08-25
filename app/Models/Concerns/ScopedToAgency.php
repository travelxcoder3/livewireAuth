<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait ScopedToAgency
{
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $user = Auth::user();
        if (!$user) abort(401);

        $query = $this->newQueryWithoutScopes()
            ->where($field ?? $this->getRouteKeyName(), $value);

        if ($this->isFillable('agency_id') || \Schema::hasColumn($this->getTable(), 'agency_id')) {
            $query->where('agency_id', $user->agency_id);
        }

        $model = $query->first();
        if (!$model) abort(404);
        return $model;
    }
}
