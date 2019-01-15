<?php

namespace AshPowell\APAnalytics\Traits;

use App\User;

trait IsAnalytic
{
    public function initializeIsAnalytic()
    {
        $this->connection = config('apanalytics.db_connection');
        $this->collection = str_before($this->getTable(), 'Analytic');
        $this->dates      = ['created_at', 'updated_at'];
        $this->guarded    = [];
    }

    /**
     * If the specified user owns this model.
     *
     * @param User $user
     */
    public function isOwner(User $user)
    {
        return $user !== null && $user->id == $this->user_id;
    }
}
