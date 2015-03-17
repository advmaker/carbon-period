<?php

use Advmaker\CarbonPeriod;
use Carbon\Carbon;

class CarbonPeriodTest extends PHPUnit_Framework_TestCase
{
    public function testOrder()
    {
        $period = CarbonPeriod::instance(Carbon::parse('+1 month'), Carbon::now());

        $this->assertEquals(Carbon::now(), $period->start());
        $this->assertEquals(Carbon::parse('+1 month'), $period->end());

        $period = CarbonPeriod::instance(Carbon::now(), Carbon::parse('+1 month'));

        $this->assertEquals(Carbon::now(), $period->start());
        $this->assertEquals(Carbon::parse('+1 month'), $period->end());
    }

    public function testToday()
    {
        $period = CarbonPeriod::today();

        $this->assertEquals(Carbon::today(), $period->start());
        $this->assertEquals(Carbon::now(), $period->end());
    }

    public function testThisDay()
    {
        $period = CarbonPeriod::thisDay();

        $this->assertEquals(Carbon::today(), $period->start());
        $this->assertEquals(Carbon::now()->endOfDay(), $period->end());
    }

    public function testLengthInYears()
    {
        $period = CarbonPeriod::instance(Carbon::now(), Carbon::parse('+1 year'));

        $this->assertEquals(1, $period->lengthInYears());
    }

    public function testLengthInMonths()
    {
        $period = CarbonPeriod::instance(Carbon::now(), Carbon::parse('+15 months'));

        $this->assertEquals(15, $period->lengthInMonths());
    }

    public function testLengthInWeeks()
    {
        $period = CarbonPeriod::instance(Carbon::now(), Carbon::parse('+4 weeks'));

        $this->assertEquals(4, $period->lengthInWeeks());
    }

    public function testLengthInDays()
    {
        $period = CarbonPeriod::instance(Carbon::now(), Carbon::parse('+20 days'));

        $this->assertEquals(20, $period->lengthInDays());
    }

    public function testLengthInHours()
    {
        $period = CarbonPeriod::instance(Carbon::now(), Carbon::parse('+19 hours'));

        $this->assertEquals(19, $period->lengthInHours());
    }

    public function testLengthInMinutes()
    {
        $period = CarbonPeriod::instance(Carbon::now(), Carbon::parse('+110 minutes'));

        $this->assertEquals(110, $period->lengthInMinutes());
    }

    public function testLengthInSeconds()
    {
        $period = CarbonPeriod::instance(Carbon::now(), Carbon::parse('+1000 seconds'));

        $this->assertEquals(1000, $period->lengthInSeconds());
    }

    public function testLastWeek()
    {
        $period = CarbonPeriod::lastWeek();

        $date = Carbon::parse('last week')->startOfDay();

        $this->assertEquals($period->start(), $date);

        $this->assertEquals($period->end(), $date->addWeek());
    }

    public function testLastMonth()
    {
        $period = CarbonPeriod::lastMonth();

        $date = Carbon::parse('first day of last month')->startOfDay();

        $this->assertEquals($period->start(), $date);

        $this->assertEquals($period->end(), $date->addMonth());
    }

    public function testThisWeek()
    {
        $period = CarbonPeriod::thisWeek();

        $start = Carbon::today();
        $end = Carbon::tomorrow();
        if ($start->dayOfWeek !== Carbon::MONDAY) {
            $start->modify('last monday');
        }


        $this->assertEquals($start, $period->start());
        $this->assertEquals($end, $period->end());
    }

    public function testThisMonth()
    {
        $period = CarbonPeriod::thisMonth();

        $date = Carbon::parse('first day of today');

        $this->assertEquals($date, $period->start());
        $this->assertEquals($date->tomorrow(), $period->end());
    }

    public function testThisYear()
    {
        $period = CarbonPeriod::thisYear();

        $date = Carbon::parse('1 January');

        $this->assertEquals($date, $period->start());
        $this->assertEquals($date->tomorrow(), $period->end());
    }

    public function testParse()
    {
        try {
            CarbonPeriod::parse('wtf', 'lol');
        } catch(Exception $e) {
            $this->assertEquals(
                'DateTime::__construct(): Failed to parse time string (wtf) at position 0 (w): The timezone could not be found in the database',
                $e->getMessage()
            );
        }

        $period = CarbonPeriod::parse('+1 day', '+1 year');

        $this->assertEquals(Carbon::parse('+1 day'), $period->start());
        $this->assertEquals(Carbon::parse('+1 year'), $period->end());
    }

    /**
     * Test the date between start and end dates in the instance
     */
    public function testContains()
    {
        $period = CarbonPeriod::lastWeek();
        $date = Carbon::parse('last week +1 day');

        $this->assertTrue($period->contains($date));
    }

    /**
     * Test the iterator for each weekday
     */
    public function testEachDayOfWeek()
    {
        $period = CarbonPeriod::lastMonth();

        $dayOfWeek = Carbon::FRIDAY;

        $date = $period->start();
        if ($dayOfWeek !== $date->dayOfWeek) {
            $date->next($dayOfWeek);
        }

        $period->eachDayOfWeek($dayOfWeek, function (CarbonPeriod $period) use ($date, $dayOfWeek) {
            /** Check dates */
            $this->assertEquals($period->start(), $date);

            /** Check days of week */
            $this->assertEquals($period->start()->dayOfWeek, $dayOfWeek);

            /** Check end day from the period */
            $this->assertEquals(
                $period->end(), $period->start()->addDay(),
                'Крайняя дата периода, в итераторе по дню недели, должна ровняться началу следующего дня, "startDate->addDay()->startOfDay()"'
            );

            $date->next($dayOfWeek);
        });
    }

    public function testAddYears()
    {
        $addValue = 2;

        $start = Carbon::today();
        $end   = Carbon::today()->addYears(2);

        $period = new CarbonPeriod($start, $end);

        $period->addYears($addValue);

        $start->addYears($addValue);
        $end->addYears($addValue);

        $this->assertEquals($period->start(), $start);

        $this->assertEquals($period->end(), $end);
    }

    public function testSubYears()
    {
        $subValue = 2;

        $start = Carbon::today()->subYears(2);
        $end   = Carbon::today();

        $period = new CarbonPeriod($start, $end);

        $period->subYears($subValue);

        $start->subYears($subValue);
        $end->subYears($subValue);

        $this->assertEquals($period->start(), $start);

        $this->assertEquals($period->end(), $end);
    }

    public function testAddMonths()
    {
        $addValue = 12;

        $start = Carbon::today();
        $end   = Carbon::today()->addMonths(12);

        $period = new CarbonPeriod($start, $end);

        $period->addMonths($addValue);

        $start->addMonths($addValue);
        $end->addMonths($addValue);

        $this->assertEquals($period->start(), $start);

        $this->assertEquals($period->end(), $end);
    }

    public function testSubMonths()
    {
        $subValue = 12;

        $start = Carbon::today()->subMonths(12);
        $end   = Carbon::today();

        $period = new CarbonPeriod($start, $end);

        $period->subMonths($subValue);

        $start->subMonths($subValue);
        $end->subMonths($subValue);

        $this->assertEquals($period->start(), $start);

        $this->assertEquals($period->end(), $end);
    }

    public function testAddDays()
    {
        $addValue = 4;

        $start = Carbon::today();
        $end   = Carbon::today()->addWeek();

        $period = new CarbonPeriod($start, $end);

        $period->addDays($addValue);

        $start->addDays($addValue);
        $end->addDays($addValue);

        $this->assertEquals($period->start(), $start);

        $this->assertEquals($period->end(), $end);
    }

    public function testSubDays()
    {
        $subValue = 4;

        $start = Carbon::today()->subWeek();
        $end   = Carbon::today();

        $period = new CarbonPeriod($start, $end);

        $period->subDays($subValue);

        $start->subDays($subValue);
        $end->subDays($subValue);

        $this->assertEquals($period->start(), $start);

        $this->assertEquals($period->end(), $end);
    }
}
