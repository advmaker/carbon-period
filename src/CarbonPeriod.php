<?php namespace Advmaker;

use Carbon\Carbon as CarbonDate;

class CarbonPeriod
{
    /**
     * First date of the period.
     *
     * @var CarbonDate
     */
    protected $startDate;

    /**
     * Last date of the period.
     *
     * @var CarbonDate
     */
    protected $endDate;

    /**
     * Swap start and end dates of the period if.
     *
     * @return $this
     */
    private function order()
    {
        if ($this->startDate > $this->endDate) {
            $tmp = clone $this->startDate;
            $this->startDate = clone $this->endDate;
            $this->endDate = clone $tmp;
            unset($tmp);
        }

        return $this;
    }

    /**
     * Create a new CarbonPeriod instance.
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     */
    public function __construct(\DateTime $startDate = null, \DateTime $endDate = null)
    {
        $this->startDate = new CarbonDate($startDate);

        $this->endDate = $endDate
            ? CarbonDate::instance($endDate)
            : $this->startDate->copy()->addDay()->startOfDay();

        $this->order();
    }

    /**
     * This is an alias for the constructor
     * that allows better fluent syntax as it allows you to do
     * CarbonPeriod::instance()->fn() rather than
     * (new CarbonPeriod())->fn()
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     *
     * @return CarbonPeriod
     */
    public static function instance(\DateTime $startDate = null, \DateTime $endDate = null)
    {
        return new static($startDate, $endDate);
    }

    /**
     * Create a CarbonPeriod from start of this day to current time.
     *
     * @return CarbonPeriod
     */
    public static function today()
    {
        return new static(CarbonDate::today(), CarbonDate::now());
    }

    /**
     * Create a CarbonPeriod from start of this day to end of this day.
     *
     * @return CarbonPeriod
     */
    public static function thisDay()
    {
        return new static(CarbonDate::today(), CarbonDate::now()->endOfDay());
    }

    /**
     * Create a CarbonPeriod of last full week.
     *
     * @return CarbonPeriod
     */
    public static function lastWeek()
    {
        $start = CarbonDate::parse('last week');

        return new static(
            $start->startOfDay(),
            $start->copy()->addWeek()
        );
    }

    /**
     * Create a CarbonPeriod of last full month.
     *
     * @return CarbonPeriod
     */
    public static function lastMonth()
    {
        $start = CarbonDate::parse('first day of last month');

        return new static(
            $start->startOfDay(),
            $start->copy()->addMonth()
        );
    }

    /**
     * Create a CarbonPeriod from closest monday to today.
     *
     * @return CarbonPeriod
     */
    public static function thisWeek()
    {
        $start = CarbonDate::today();
        $end = CarbonDate::tomorrow();
        if ($start->dayOfWeek !== CarbonDate::MONDAY) {
            $start->modify('last monday');
        }

        return new static($start, $end);
    }

    /**
     * Create a CarbonPeriod instance from first day of current month to current date.
     *
     * @return CarbonPeriod
     */
    public static function thisMonth()
    {
        $start = CarbonDate::parse('first day of now');
        return new static($start->startOfDay(), $start->copy()->tomorrow());
    }

    /**
     * Create a CarbonPeriod instance from 1 January of current year to current date.
     *
     * @return CarbonPeriod
     */
    public static function thisYear()
    {
        $start = CarbonDate::parse('1 January');
        return new static($start, $start->copy()->tomorrow());
    }

    /**
     * Create a CarbonPeriod instance from two strings.
     *
     * @param string $startDate
     * @param string $endDate
     *
     * @return CarbonPeriod
     */
    public static function parse($startDate, $endDate)
    {
        return new static(
            CarbonDate::parse($startDate),
            CarbonDate::parse($endDate)
        );
    }

    /**
     * Get the mutable start date from the instance.
     *
     * @return CarbonDate
     */
    public function start()
    {
        return $this->startDate;
    }

    /**
     * Get the immutable start date from the instance.
     *
     * @return CarbonDate|null
     */
    public function copyStart()
    {
        return $this->start()->copy();
    }

    /**
     * Get the mutable end date from the instance.
     *
     * @return CarbonDate|null
     */
    public function end()
    {
        return $this->endDate;
    }

    /**
     * Get the immutable end date from the instance.
     *
     * @return CarbonDate|null
     */
    public function copyEnd()
    {
        return $this->end()->copy();
    }

    /**
     * Set the internal iterator with interval for the instance.
     *
     * @param \DateInterval $interval
     * @param \Closure      $callback
     *
     * @return CarbonDate|$this
     */
    public function each(\DateInterval $interval, \Closure $callback)
    {
        $period = new static($this->copyStart(), $this->copyStart()->add($interval));

        do {
            $callback(new static(
                $period->copyStart(),
                $period->copyEnd() > $this->end() ? $this->end() : $period->copyEnd()
            ));
        } while ($period->add($interval)->copyStart() < $this->end());

        return $this;
    }

    /**
     * Set the internal iterator with day interval for the instance.
     *
     * @param int      $days
     * @param \Closure $callback
     *
     * @return CarbonDate|$this
     */
    public function eachDays($days = 1, \Closure $callback)
    {
        return $this->each(new \DateInterval("P{$days}D"), $callback);
    }

    /**
     * Set the internal iterator with week interval for the instance.
     *
     * @param int      $weeks
     * @param \Closure $callback
     * @param bool     $onlyFullWeek
     *
     * @return CarbonDate|$this
     */
    public function eachWeeks($weeks = 1, \Closure $callback, $onlyFullWeek = false)
    {
        if ($this->lengthInWeeks() > 0) {
            return $this->each(
                new \DateInterval("P{$weeks}W"),
                function (CarbonPeriod $period) use ($weeks, $callback, $onlyFullWeek) {
                    if (!$onlyFullWeek || $period->lengthInWeeks() === $weeks) {
                        call_user_func_array($callback, func_get_args());
                    }
                }
            );
        }

        return $this;
    }

    /**
     * Set the internal iterator with month interval for the instance.
     *
     * @param int      $months
     * @param \Closure $callback
     * @param bool     $onlyFullMonth
     *
     * @return CarbonDate|$this
     */
    public function eachMonths($months = 1, \Closure $callback, $onlyFullMonth = false)
    {
        if ($this->lengthInMonths() > 0) {
            return $this->each(
                new \DateInterval("P{$months}M"),
                function (CarbonPeriod $period) use ($months, $callback, $onlyFullMonth) {
                    if (!$onlyFullMonth || $period->lengthInMonths() === $months) {
                        call_user_func_array($callback, func_get_args());
                    }
                }
            );
        }

        return $this;
    }

    /**
     * Set the internal iterator for day of week for the instance.
     *
     * @param int      $dayOfWeek
     * @param \Closure $callback
     *
     * @return $this
     */
    public function eachDayOfWeek($dayOfWeek, \Closure $callback)
    {
        $start = $this->copyStart();
        if ($start->dayOfWeek !== $dayOfWeek) {
            $start->next($dayOfWeek);
        }

        if ($start < $this->end()) {
            $period = new static($start, $this->end());

            $period->eachDays(CarbonDate::DAYS_PER_WEEK, function (CarbonPeriod $period) use ($callback) {
                $callback(new static($period->copyStart(), $period->copyStart()->addDay()));
            });
        }

        return $this;
    }

    /**
     * Get the difference between start and end dates of the period in years.
     *
     * @return int
     */
    public function lengthInYears()
    {
        return $this->start()->diffInYears($this->end());
    }

    /**
     * Get the difference between start and end dates of the period in months.
     *
     * @return int
     */
    public function lengthInMonths()
    {
        return $this->start()->diffInMonths($this->end());
    }

    /**
     * Get the difference between start and end dates of the period in weeks.
     *
     * @return int
     */
    public function lengthInWeeks()
    {
        return $this->start()->diffInWeeks($this->end());
    }

    /**
     * Get the difference between start and end dates of the period in days.
     *
     * @return int
     */
    public function lengthInDays()
    {
        return $this->start()->diffInDays($this->end());
    }

    /**
     * Get the difference between start and end dates of the period in hours.
     *
     * @return int
     */
    public function lengthInHours()
    {
        return $this->start()->diffInHours($this->end());
    }

    /**
     * Get the difference between start and end dates of the period in minutes.
     *
     * @return int
     */
    public function lengthInMinutes()
    {
        return $this->start()->diffInMinutes($this->end());
    }

    /**
     * Get the difference between start and end dates of the period in seconds.
     *
     * @return int
     */
    public function lengthInSeconds()
    {
        return $this->start()->diffInSeconds($this->end());
    }

    /**
     * Add \DateInterval to the instance.
     *
     * @param \DateInterval $interval
     *
     * @return $this
     */
    public function add(\DateInterval $interval)
    {
        $this->start()->add($interval);
        $this->end()->add($interval);

        return $this;
    }

    /**
     * Sub \DateInterval from the instance.
     *
     * @param \DateInterval $interval
     *
     * @return $this
     */
    public function sub(\DateInterval $interval)
    {
        $this->start()->sub($interval);
        $this->end()->sub($interval);

        return $this;
    }

    /**
     * Add years to the period.
     *
     * @param int $value
     *
     * @return CarbonPeriod
     */
    public function addYears($value)
    {
        return $this->add(new \DateInterval("P{$value}Y"));
    }

    /**
     * Remove years from the period.
     *
     * @param int $value
     *
     * @return CarbonPeriod
     */
    public function subYears($value)
    {
        return $this->sub(new \DateInterval("P{$value}Y"));
    }

    /**
     * Add months to the period.
     *
     * @param int $value
     *
     * @return CarbonPeriod
     */
    public function addMonths($value)
    {
        return $this->add(new \DateInterval("P{$value}M"));
    }

    /**
     * Remove months from the period.
     *
     * @param int $value
     *
     * @return CarbonPeriod
     */
    public function subMonths($value)
    {
        return $this->sub(new \DateInterval("P{$value}M"));
    }

    /**
     * Add days to the period.
     *
     * @param int $value
     *
     * @return CarbonPeriod
     */
    public function addDays($value)
    {
        return $this->add(new \DateInterval("P{$value}D"));
    }

    /**
     * Remove days from the period.
     *
     * @param int $value
     *
     * @return CarbonPeriod
     */
    public function subDays($value)
    {
        return $this->sub(new \DateInterval("P{$value}D"));
    }

    /**
     * Set time of start date to 00:00 and time of end date to 23:59
     *
     * @return $this
     */
    public function setTimeToStartEndPoints()
    {
        $this->start()->startOfDay();
        $this->end()->endOfDay();

        return $this;
    }

    /**
     * Determines if the instances contains a date.
     *
     * @see \Carbon\Carbon::between
     * @param CarbonDate|string $date
     * @param bool              $equal Indicates if a > and < comparison should be used or <= or >=
     *
     * @return bool
     */
    public function contains($date, $equal = true)
    {
        if (!$date instanceof CarbonDate) {
            $date = CarbonDate::parse($date);
        }

        return $date->between($this->start(), $this->end(), $equal);
    }
}
