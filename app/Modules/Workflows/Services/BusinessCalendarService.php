<?php

namespace App\Modules\Workflows\Services;

use Carbon\Carbon;
use App\Settings\GeneralSettings;

class BusinessCalendarService
{
    protected string $timezone;
    protected int $workStartHour = 9;
    protected int $workEndHour = 17;

    public function __construct(GeneralSettings $settings)
    {
        $this->timezone = $settings->display_timezone ?? 'Asia/Jakarta';
    }

    /**
     * Calculate a deadline by adding business days, ensuring the result lands on a work day and at the end of business hours.
     */
    public function addBusinessDays(Carbon $start, int $days): Carbon
    {
        $date = $start->copy()->tz($this->timezone);
        
        // If the start time is past working hours or on weekend, shift to next business morning
        if (!$this->isBusinessDay($date) || $date->hour >= $this->workEndHour) {
            $date = $this->nextBusinessMorning($date);
        }

        while ($days > 0) {
            $date->addDay();
            if ($this->isBusinessDay($date)) {
                $days--;
            }
        }

        // Set deadline to the end of the work day
        $date->setTime($this->workEndHour, 0, 0);

        return $date;
    }

    public function addBusinessHours(Carbon $start, int $hours): Carbon
    {
        $date = $start->copy()->tz($this->timezone);

        if (!$this->isBusinessDay($date) || $date->hour >= $this->workEndHour) {
            $date = $this->nextBusinessMorning($date);
        }

        while ($hours > 0) {
            if ($this->isBusinessDay($date) && $date->hour >= $this->workStartHour && $date->hour < $this->workEndHour) {
                $date->addHour();
                $hours--;
            } else {
                $date->addHour();
            }
            
            // Recheck if we stepped out of business hours
            if ($date->hour >= $this->workEndHour) {
                $date = $this->nextBusinessMorning($date);
            }
        }
        
        return $date;
    }

    public function isBusinessDay(Carbon $date): bool
    {
        // Skip weekends
        if ($date->isWeekend()) {
            return false;
        }

        // TODO: Check against holidays table/config if implemented
        
        return true;
    }

    public function nextBusinessMorning(Carbon $date): Carbon
    {
        $next = $date->copy();
        do {
            $next->addDay();
        } while (!$this->isBusinessDay($next));

        $next->setTime($this->workStartHour, 0, 0);
        return $next;
    }

    public function endOfBusinessDay(Carbon $date): Carbon
    {
        return $date->copy()->tz($this->timezone)->setTime($this->workEndHour, 0, 0);
    }
}
