<?php

namespace AshPowell\APAnalytics\Traits;

use App\User;

trait IsAnalytic
{
    public function initializeIsAnalytic()
    {
        $this->connection = config('apanalytics.db_connection');
        $this->collection = str_plural(str_before($this->getTable(), '_'));
        $this->dates      = ['created_at', 'updated_at'];
        $this->guarded    = [];
    }

    public function canViewCollection(User $user)
    {
        return $user->isAnyAdmin();
    }
}
