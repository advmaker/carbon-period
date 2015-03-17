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

        return $this;
    }

    /**
     * This is an alias for the constructor
     * that allows better fluent syntax as it allows you to do
     * CarbonPeriod::instance()->fn() rather than
     * (new CarbonPeriod())->fn()
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return static
     */
    public static function instance(\DateTime $startDate = null, \DateTime $endDate = null)
    {
        return new static($startDate, $endDate);
    }

    /**
     * Create a CarbonPeriod from start of this day to current time.
     *
     * @return static
     */
    public static function today()
    {
        return new static(CarbonDate::today(), CarbonDate::now());
    }

    /**
     * Create a CarbonPeriod from start of this day to end of this day.
     *
     * @return static
     */
    public static function thisDay()
    {
        return new static(CarbonDate::today(), CarbonDate::now()->endOfDay());
    }

    /**
     * Create a CarbonPeriod of last full week.
     *
     * @return static
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
     * @return static
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
     * @return static
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
     * @return static
     */
    public static function thisMonth()
    {
        $start = CarbonDate::parse('first day of now');
        return new static($start->startOfDay(), $start->copy()->tomorrow());
    }

    /**
     * Create a CarbonPeriod instance from 1 January of current year to current date.
     *
     * @return static
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
     * @return static
     */
    public static function parse($startDate, $endDate)
    {
        return new static(
            CarbonDate::parse($startDate),
            CarbonDate::parse($endDate)
        );
    }

    /**
     * Get the start date from the instance.
     *
     * @return CarbonDate
     */
    public function start()
    {
        return $this->startDate->copy();
    }

    /**
     * Get the end date from the instance.
     *
     * @return CarbonDate|null
     */
    public function end()
    {
        return $this->endDate->copy();
    }

    /**
     * Set the internal iterator with interval for the instance.
     *
     * @param \DateInterval $interval
     * @param \Closure $callback
     * @return CarbonDate|$this
     */
    public function each(\DateInterval $interval, \Closure $callback)
    {
        $period = new static($this->start(), $this->start()->add($interval));

        do {
            $callback(new static(
                $period->start(),
                $period->end() > $this->endDate ? $this->endDate : $period->end()
            ));
        } while ($period->add($interval)->start() < $this->endDate);

        return $this;
    }

    /**
     * Set the internal iterator with day interval for the instance.
     *
     * @param int $days
     * @param callable $callback
     * @return CarbonDate|$this
     */
    public function eachDays($days = 1, \Closure $callback)
    {
        return $this->each(new \DateInterval("P{$days}D"), $callback);
    }

    /**
     * Set the internal iterator with week interval for the instance.
     *
     * @param int $weeks
     * @param callable $callback
     * @param bool $onlyFullWeek
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
     * @param int $months
     * @param callable $callback
     * @param bool $onlyFullMonth
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
     * @param $dayOfWeek
     * @param callable $callback
     * @return $this
     */
    public function eachDayOfWeek($dayOfWeek, \Closure $callback)
    {
        $start = $this->startDate->copy();
        if ($start->dayOfWeek !== $dayOfWeek) {
            $start->next($dayOfWeek);
        }

        if ($start < $this->endDate) {
            $period = new static($start, $this->endDate);

            $period->eachDays(CarbonDate::DAYS_PER_WEEK, function (CarbonPeriod $period) use ($callback) {
                $callback(new static($period->start(), $period->start()->addDay()));
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
        return $this->startDate->diffInYears($this->endDate);
    }

    /**
     * Get the difference between start and end dates of the period in months.
     *
     * @return int
     */
    public function lengthInMonths()
    {
        return $this->startDate->diffInMonths($this->endDate);
    }

    /**
     * Get the difference between start and end dates of the period in weeks.
     *
     * @return int
     */
    public function lengthInWeeks()
    {
        return $this->startDate->diffInWeeks($this->endDate);
    }

    /**
     * Get the difference between start and end dates of the period in days.
     *
     * @return int
     */
    public function lengthInDays()
    {
        return $this->startDate->diffInDays($this->endDate);
    }

    /**
     * Get the difference between start and end dates of the period in hours.
     *
     * @return int
     */
    public function lengthInHours()
    {
        return $this->startDate->diffInHours($this->endDate);
    }

    /**
     * Get the difference between start and end dates of the period in minutes.
     *
     * @return int
     */
    public function lengthInMinutes()
    {
        return $this->startDate->diffInMinutes($this->endDate);
    }

    /**
     * Get the difference between start and end dates of the period in seconds.
     *
     * @return int
     */
    public function lengthInSeconds()
    {
        return $this->startDate->diffInSeconds($this->endDate);
    }

    /**
     * Add \DateInterval to the instance.
     *
     * @param \DateInterval $interval
     * @return $this
     */
    public function add(\DateInterval $interval)
    {
        $this->startDate->add($interval);
        $this->endDate->add($interval);

        return $this;
    }

    /**
     * Sub \DateInterval from the instance.
     *
     * @param \DateInterval $interval
     * @return $this
     */
    public function sub(\DateInterval $interval)
    {
        $this->startDate->sub($interval);
        $this->endDate->sub($interval);

        return $this;
    }

    /**
     * Add years to the period.
     *
     * @param $value
     * @return CarbonPeriod
     */
    public function addYears($value)
    {
        return $this->add(new \DateInterval("P{$value}Y"));
    }

    /**
     * Remove years from the period.
     *
     * @param $value
     * @return CarbonPeriod
     */
    public function subYears($value)
    {
        return $this->sub(new \DateInterval("P{$value}Y"));
    }

    /**
     * Add months to the period.
     *
     * @param $value
     * @return CarbonPeriod
     */
    public function addMonths($value)
    {
        return $this->add(new \DateInterval("P{$value}M"));
    }

    /**
     * Remove months from the period.
     *
     * @param $value
     * @return CarbonPeriod
     */
    public function subMonths($value)
    {
        return $this->sub(new \DateInterval("P{$value}M"));
    }

    /**
     * Add days to the period.
     *
     * @param $value
     * @return CarbonPeriod
     */
    public function addDays($value)
    {
        return $this->add(new \DateInterval("P{$value}D"));
    }

    /**
     * Remove days from the period.
     *
     * @param $value
     * @return CarbonPeriod
     */
    public function subDays($value)
    {
        return $this->sub(new \DateInterval("P{$value}D"));
    }

    /**
     * Determines if the instances contains a date.
     *
     * @see \Carbon\Carbon::between
     * @param CarbonDate $date
     * @param bool $equal Indicates if a > and < comparison should be used or <= or >=
     * @return bool
     */
    public function contains(CarbonDate $date, $equal = true)
    {
        return $date->between($this->startDate, $this->endDate, $equal);
    }
}
