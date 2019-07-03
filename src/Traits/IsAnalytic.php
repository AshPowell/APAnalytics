<?php

namespace AshPowell\APAnalytics\Traits;

use App\User;
use Illuminate\Support\Str;

trait IsAnalytic
{
    public function initializeIsAnalytic()
    {
        $this->connection = config('apanalytics.db_connection');
        $this->collection = Str::plural(Str::before($this->getTable(), '_'));
        $this->dates      = ['created_at', 'updated_at'];
        $this->guarded    = [];
    }

    public function canViewCollection(User $user = null)
    {
        if (app()->runningInConsole()) {
            return true;
        }

        if (!$user) {
            return false;
        }

        return $user->isAnyAdmin();
    }
}
