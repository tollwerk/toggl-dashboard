<?php

/**
 * Toggl Dashboard
 *
 * @category    Apparat
 * @package     Apparat\Server
 * @subpackage  Tollwerk\Toggl\Domain\Report
 * @author      Joschi Kuphal <joschi@tollwerk.de> / @jkphl
 * @copyright   Copyright © 2016 Joschi Kuphal <joschi@tollwerk.de> / @jkphl
 * @license     http://opensource.org/licenses/MIT The MIT License (MIT)
 */

/***********************************************************************************
 *  The MIT License (MIT)
 *
 *  Copyright © 2016 Joschi Kuphal <joschi@kuphal.net> / @jkphl
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of
 *  this software and associated documentation files (the "Software"), to deal in
 *  the Software without restriction, including without limitation the rights to
 *  use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 *  the Software, and to permit persons to whom the Software is furnished to do so,
 *  subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 *  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 *  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 *  IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 *  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 ***********************************************************************************/

namespace Tollwerk\Toggl\Domain\Report;

use Tollwerk\Toggl\Domain\Model\Contract;
use Tollwerk\Toggl\Domain\Model\Stats;

/**
 * Day report
 *
 * @package Apparat\Server
 * @subpackage Tollwerk\Toggl\Domain\Report
 */
class DayReport
{
    /**
     * Date
     *
     * @var \DateTimeImmutable
     */
    protected $date;
    /**
     * Business holiday name
     *
     * @var bool|string
     */
    protected $businessHoliday = false;
    /**
     * Personal holiday name
     *
     * @var bool|string
     */
    protected $personalHoliday = false;
    /**
     * Excused holiday
     *
     * @var bool
     */
    protected $excused = false;
    /**
     * Overtime reduction holiday
     *
     * @var bool
     */
    protected $overtime = false;
    /**
     * Working day state
     *
     * @var bool|null
     */
    protected $workingDay = null;
    /**
     * User contract
     *
     * @var Contract
     */
    protected $contract;
    /**
     * Target time
     *
     * @var float
     */
    protected $timeTarget = 0;
    /**
     * Actual time
     *
     * @var float
     */
    protected $timeActual = 0;
    /**
     * Time status
     *
     * @var float
     */
    protected $timeStatus = 0;
    /**
     * Target billable time
     *
     * @var float
     */
    protected $billableTarget = 0;
    /**
     * Actual billable time
     *
     * @var float
     */
    protected $billableActual = 0;
    /**
     * Billable status
     *
     * @var float
     */
    protected $billableStatus = 0;
    /**
     * Revenue target
     *
     * @var float
     */
    protected $revenueTarget = 0;
    /**
     * Revenue status
     *
     * @var float
     */
    protected $revenueStatus = 0;
    /**
     * Billable rate
     *
     * @var float
     */
    protected $rate;
    /**
     * Default holiday
     *
     * @var boolean
     */
    const DEFAULT_HOLIDAY = true;

    /**
     * DayReport constructor
     *
     * @param \DateTimeImmutable $date Date
     * @param float $rate Billable rate
     */
    public function __construct(\DateTimeImmutable $date, $rate)
    {
        $this->date = $date;
        $this->rate = $rate;
    }

    /**
     * Return the date
     *
     * @return \DateTimeImmutable
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Return the day
     *
     * @return int Day
     */
    public function getDay()
    {
        return intval($this->date->format('j'));
    }

    /**
     * Return the month
     *
     * @return int Month
     */
    public function getMonth()
    {
        return intval($this->date->format('n'));
    }

    /**
     * Return the ISO-8601 year
     *
     * @return int Year
     */
    public function getYear()
    {
        return intval($this->date->format('o'));
    }

    /**
     * Return the ISO-8601 week
     *
     * @return int Week
     */
    public function getWeek()
    {
        return intval($this->date->format('W'));
    }

    /**
     * Return the day number of the year
     *
     * @return int Day number of the year
     */
    public function getYearDay()
    {
        return intval($this->date->format('z'));
    }

    /**
     * Return the weekday number
     *
     * @return int Weekday number
     */
    public function getWeekDay()
    {
        return intval($this->date->format('w'));
    }

    /**
     * Return the business holiday name of this day
     *
     * @return bool|string
     */
    public function getBusinessHoliday()
    {
        return $this->businessHoliday;
    }

    /**
     * Set the business holiday name of this day
     *
     * @param bool|string $businessHoliday Business holiday name
     * @return DayReport Self reference
     */
    public function setBusinessHoliday($businessHoliday)
    {
        $this->businessHoliday = $businessHoliday;
        return $this;
    }

    /**
     * Return the personal holiday state / name of this day
     *
     * @return bool|string Personal holiday state / name
     */
    public function getPersonalHoliday()
    {
        return $this->personalHoliday;
    }

    /**
     * Set the personal holiday state / name of this day
     *
     * @param bool|string $personalHoliday Personal holiday state / name
     * @param bool $excused Excused holiday
     * @param bool $overtime Overtime reduction holiday
     * @return DayReport Self reference
     */
    public function setPersonalHoliday($personalHoliday = self::DEFAULT_HOLIDAY, $excused = false, $overtime = false)
    {
        $this->personalHoliday = $personalHoliday;
        $this->excused = !!$excused;
        $this->overtime = !!$overtime;
        return $this;
    }

    /**
     * Return whether this is a working day
     *
     * @return boolean Is working day
     */
    public function isWorkingDay()
    {
        if ($this->workingDay === null) {
            $this->workingDay = ($this->contract instanceof Contract)
                && array_key_exists($this->getWeekDay(), $this->contract->getWorkingDays());
        }
        return $this->workingDay;
    }

    /**
     * Return wheter this is a (business or personal) holiday
     *
     * @param bool $truePersonalHolidaysOnly Exclude overtime reduction holidays
     * @return bool Holiday
     */
    public function isHoliday($truePersonalHolidaysOnly = false)
    {
        $isPersonalHoliday = !!$this->getPersonalHoliday();
        if ($truePersonalHolidaysOnly) {
            $isPersonalHoliday = $isPersonalHoliday && !$this->isExcused() && !$this->isOvertime();
        }
        return ($this->contract instanceof Contract) && ($this->getBusinessHoliday() || $isPersonalHoliday);
    }

    /**
     * Return the effective contract
     *
     * @return Contract Effective contract
     */
    public function getContract()
    {
        return $this->contract;
    }

    /**
     * Set the effective contract
     *
     * @param Contract $contract Effective Contract
     * @return DayReport Self reference
     */
    public function setContract($contract)
    {
        $this->contract = $contract;
        return $this;
    }

    /**
     * Apply this day's contract
     *
     * @param float $monthlyWorkingDayShare Monthly working day share
     * @param int $workingDays Efficive working days the current (monthly) contract applies to
     */
    public function applyContract($monthlyWorkingDayShare, $monthlyWorkingDays)
    {
        if ($this->isWorkingDay() && !$this->isHoliday()) {
            $this->timeTarget = $this->contract->getWorkingHoursPerDay();
            $this->revenueTarget = $this->contract->getCostsPerMonth() * $monthlyWorkingDayShare / $monthlyWorkingDays;
            $this->billableTarget = $this->revenueTarget / $this->rate;
//            echo $this->date->format('c').' - '.$this->billableTarget.' -> '.$this->revenueTarget.PHP_EOL;
        }
    }

    /**
     * Apply this day's user stats
     *
     * @param Stats $stats User stats
     */
    public function applyStats(Stats $stats)
    {
        $this->timeActual = $stats->getTotal();
        $this->timeStatus = $this->timeTarget ? ($this->timeActual / $this->timeTarget) : null;
        $this->billableActual = $stats->getBillable();
        $this->billableStatus = $this->billableTarget ? ($this->billableActual / $this->billableTarget) : null;
        $this->revenueStatus = $this->revenueTarget ? ($stats->getBillableSum() / $this->revenueTarget) : null;
    }

    /**
     * Get the time target
     *
     * @return float Time target
     */
    public function getTimeTarget()
    {
        return $this->timeTarget;
    }

    /**
     * Get the actual time
     *
     * @return float Actual time
     */
    public function getTimeActual()
    {
        return $this->timeActual;
    }

    /**
     * Get the time status
     *
     * @return float Time status
     */
    public function getTimeStatus()
    {
        return $this->timeStatus;
    }

    /**
     * Get the billable time target
     *
     * @return float Billable time target
     */
    public function getBillableTarget()
    {
        return $this->billableTarget;
    }

    /**
     * Get the actual billable time
     *
     * @return float Actual billable time
     */
    public function getBillableActual()
    {
        return $this->billableActual;
    }

    /**
     * Get the billable status
     *
     * @return float Billable status
     */
    public function getBillableStatus()
    {
        return $this->billableStatus;
    }

    /**
     * Get the revenue target
     *
     * @return float Revenue target
     */
    public function getRevenueTarget()
    {
        return $this->revenueTarget;
    }

    /**
     * Get the revenue status
     *
     * @return float Revenue status
     */
    public function getRevenueStatus()
    {
        return $this->revenueStatus;
    }

    /**
     * Get the hourly rate
     *
     * @return float Hourly rate
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Return whether this is an excused holiday
     *
     * @return boolean Excused holiday
     */
    public function isExcused()
    {
        return $this->excused;
    }

    /**
     * Return whether this is an overtime reduction holiday
     *
     * @return boolean Overtime reduction holiday
     */
    public function isOvertime()
    {
        return $this->overtime;
    }
}
