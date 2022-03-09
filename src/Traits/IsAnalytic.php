<?php

namespace AshPowell\APAnalytics\Traits;

use App\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Str;

trait IsAnalytic
{
    public function initializeIsAnalytic()
    {
        $this->connection = config('apanalytics.db_connection');
        $this->collection = Str::plural(Str::before($this->getTable(), '_analytic'));
        $this->dates      = ['created_at', 'updated_at'];
        $this->guarded    = [];
    }

    public function canViewCollection(User $user = null)
    {
        if (app()->runningInConsole()) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $user->isAnyAdmin();
    }

    /**
     * Returns the month on month analytic count.
     *
     * @param  string  $period
     * @return array
     */
    public static function getCumulativeGrowthData($period = 6)
    {
        $endDate   = now();
        $startDate = $endDate->copy()->subMonths($period);
        $period    = CarbonPeriod::create($startDate, '1 month', $endDate);

        $output = [];
        foreach ($period as $date) {
            $date          = $date->format('d-m-Y');
            $output[$date] = (new static)->getCountForDate($date, 'd-m-Y');
        }

        return $output;
    }

    /**
     * Returns the analytic count for the specified month.
     *
     * @param  string  $date
     * @param  string  $format
     * @return int
     */
    public static function getCountForDate($date, $format)
    {
        $currentDate = Carbon::createFromFormat($format, $date);

        $currentCount = (new static)
            ->where('created_at', '<=', $currentDate)
            ->count();

        return $currentCount;
    }
}
