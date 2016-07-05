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
use Tollwerk\Toggl\Domain\Model\Day;
use Tollwerk\Toggl\Domain\Model\Stats;
use Tollwerk\Toggl\Domain\Model\User;
use Tollwerk\Toggl\Domain\Repository\ContractRepository;
use Tollwerk\Toggl\Domain\Repository\DayRepository;
use Tollwerk\Toggl\Domain\Repository\StatsRepository;
use Tollwerk\Toggl\Ports\App;

class UserReport
{
    /**
     * User
     *
     * @var User
     */
    protected $user;
    /**
     * Start date
     *
     * @var \DateTimeImmutable
     */
    protected $from;
    /**
     * End date (including)
     *
     * @var \DateTimeImmutable
     */
    protected $to;
    /**
     * Report days
     *
     * @var DayReport[]
     */
    protected $days = [];
    /**
     * Weeks
     *
     * @var array
     */
    protected $weeks = [];
    /**
     * Months
     *
     * @var array
     */
    protected $months = [];
    /**
     * Year
     *
     * @var int
     */
    protected $year;
    /**
     * Day repository
     *
     * @var DayRepository
     */
    protected $dayRepository;
    /**
     * Contract repository
     *
     * @var ContractRepository
     */
    protected $contractRepository;
    /**
     * Stats repository
     *
     * @var StatsRepository
     */
    protected $statsRepository;
    /**
     * Total number of working days
     *
     * @var int
     */
    protected $workingDays = 0;
    /**
     * Working days per week and contract
     *
     * @var array
     */
    protected $workingDaysPerWeekAndContract;
    /**
     * Working days per month and contract
     *
     * @var array
     */
    protected $workingDaysPerMonthAndContract;
    /**
     * Personal holidays
     *
     * @var int
     */
    protected $personalHolidays = 0;
    /**
     * Planned personal holidays
     *
     * @var int
     */
    protected $personalHolidaysPlanned = 0;
    /**
     * Personal holidays that have already past
     *
     * @var int
     */
    protected $personalHolidaysPast = 0;
    /**
     * User contracts
     *
     * @var Contract[]
     */
    protected $contracts = [];
    /**
     * User contract shares
     *
     * @var array
     */
    protected $yearlyContractSharesByMonth = [];

    /**
     * UserReport constructor
     *
     * @param User $user User
     * @param \DateTimeImmutable $from Start date
     * @param \DateTimeImmutable $to End date (including)
     */
    protected function __construct(User $user, \DateTimeImmutable $from, \DateTimeImmutable $to)
    {
        $this->user = $user;
        $this->from = $from;
        $this->to = $to;
        $this->init();
    }

    /**
     * Initialize the report
     */
    protected function init()
    {
        $this->year = intval($this->from->format('Y'));
        $entityManager = \Tollwerk\Toggl\Ports\App::getEntityManager();
        $this->dayRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\Day');
        $this->contractRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\Contract');
        $this->statsRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\Stats');

        $this->initDays(floatval(App::getConfig('common.rate')));
        $this->initContracts();
        $this->initBusinessHolidays();
        $this->initPersonalHolidays();
        $this->initWorkingDays();

        $this->applyContracts();
        $this->applyStats();
    }

    /**
     * Apply the user stats
     */
    protected function applyStats()
    {
        // Run through all user stats in the date range
        /** @var Stats $userStats */
        foreach ($this->statsRepository->getUserStats($this->user, $this->from, $this->to) as $userStats) {
            $this->days[$userStats->getDate()->format('z')]->applyStats($userStats);
        }
    }

    /**
     * Apply the user contracts
     */
    protected function applyContracts()
    {
        // Run through all months
        foreach ($this->months as $month => $monthDays) {
            /** @var DayReport $monthDay */
            foreach ($monthDays as $monthDay) {
                $contract = $monthDay->getContract();
                if ($contract instanceof Contract) {
                    $contractId = $contract->getId();
                    $contractMonthWorkingDays = $this->workingDaysPerMonthAndContract[$month][$contractId];
                    $contractWorkingDayShare = $contractMonthWorkingDays /
                        array_sum($this->workingDaysPerMonthAndContract[$month]);
                    $monthDay->applyContract(
                        $this->yearlyContractSharesByMonth[$contractId][$month],
                        $contractWorkingDayShare,
                        $contractMonthWorkingDays
                    );
                }
            }
        }
    }

    /**
     * Initialize the working days
     */
    protected function initWorkingDays()
    {
        $this->workingDaysPerWeekAndContract = array_fill_keys(array_keys($this->weeks), []);
        $this->workingDaysPerMonthAndContract = array_fill_keys(array_keys($this->months), []);

        // Run through all working days and collect the working day states
        foreach ($this->days as $reportDay) {
            // If this is a working day for the user
            if ($reportDay->isWorkingDay() && !$reportDay->getBusinessHoliday()) {
                ++$this->workingDays;
                $contractId = $reportDay->getContract()->getId();

                // If this is not a personal holiday
                if (!$reportDay->getPersonalHoliday()) {

                    // Weekly working days
                    if ($reportDay->getYear() == $this->year) {
                        $week = $reportDay->getWeek();
                        if (!array_key_exists($contractId, $this->workingDaysPerWeekAndContract[$week])) {
                            $this->workingDaysPerWeekAndContract[$week][$contractId] = 0;
                        }
                        ++$this->workingDaysPerWeekAndContract[$week][$contractId];
                    }

                    // Monthly working days
                    $month = $reportDay->getMonth();
                    if (!array_key_exists($contractId, $this->workingDaysPerMonthAndContract[$month])) {
                        $this->workingDaysPerMonthAndContract[$month][$contractId] = 0;
                    }
                    ++$this->workingDaysPerMonthAndContract[$month][$contractId];
                }
            }
        }
    }

    /**
     * Initialize the user contracts
     */
    protected function initContracts()
    {
        /** @var Contract[] $contracts */
        $contracts = $this->contractRepository->getUserContracts($this->user, $this->from, $this->to);
        /** @var Contract $contract */
        $contract = array_shift($contracts);
        $this->contracts[$contract->getId()] = $contract;
        $this->yearlyContractSharesByMonth[$contract->getId()] = array_fill_keys(array_keys($this->months), 0);

        // Reverse-run through all days
        foreach (array_reverse(array_keys($this->days)) as $yearDay) {
            // Get the next contract if necessary
            if ($this->days[$yearDay]->getDate() < $contract->getDate()) {
                if (!count($contracts)) {
                    break;
                }
                $contract = array_shift($contracts);
                $this->contracts[$contract->getId()] = $contract;
                $this->yearlyContractSharesByMonth[$contract->getId()] = array_fill_keys(array_keys($this->months), 0);
            }

            $this->days[$yearDay]->setContract($contract);
            ++$this->yearlyContractSharesByMonth[$contract->getId()][$this->days[$yearDay]->getMonth()];
        }

        // Normalize the contract shares
        foreach ($this->yearlyContractSharesByMonth as $contractId => $yearlyContractSharesByMonthByMonth) {
            foreach ($yearlyContractSharesByMonthByMonth as $month => $contractShare) {
                $this->yearlyContractSharesByMonth[$contractId][$month] /= count($this->days);
            }
        }
    }

    /**
     * Initialize the business holidays
     */
    protected function initBusinessHolidays()
    {
        // Run through all business holidays in the date range
        /** @var Day $businessHoliday */
        foreach ($this->dayRepository->getBusinessHolidays($this->from, $this->to) as $businessHoliday) {
            $yearDay = $businessHoliday->getDate()->format('z');
            $this->days[$yearDay]->setBusinessHoliday($businessHoliday->getName());
        }
    }

    /**
     * Initialize the personal holidays
     */
    protected function initPersonalHolidays()
    {
        $today = new \DateTimeImmutable('now');

        // Run through all business holidays in the date range
        /** @var Day $personalHoliday */
        foreach ($this->dayRepository->getPersonalHolidays($this->user, $this->from, $this->to) as $personalHoliday) {
            $yearDay = $personalHoliday->getDate()->format('z');
            $this->days[$yearDay]->setPersonalHoliday($personalHoliday->getName() ?: DayReport::DEFAULT_HOLIDAY);
            ++$this->personalHolidaysPlanned;

            if ($personalHoliday->getDate() < $today) {
                ++$this->personalHolidaysPast;
            }
        }

        // Calculate the holiday entitlement
        foreach ($this->yearlyContractSharesByMonth as $contractId => $yearlyContractSharesByMonthByMonth) {
            $contractHoliday = $this->contracts[$contractId]->getHolidaysPerYear();
            $this->personalHolidays += round(array_sum($yearlyContractSharesByMonthByMonth) * $contractHoliday);
        }
    }

    /**
     * Initialize the report days
     *
     * @param float $rate Billable rate
     */
    protected function initDays($rate)
    {
        // Run through all days of the report
        for ($day = clone $this->from; $day <= $this->to; $day = $day->modify('+1 day')) {
            $reportDay = new DayReport(clone $day, $rate);
            $yearDay = $reportDay->getYearDay();
            $this->days[$yearDay] = $reportDay;

            // Register with the respective ISO-8601 calendar week
            if ($reportDay->getYear() == $this->year) {
                $week = $reportDay->getWeek();
                if (!array_key_exists($week, $this->weeks)) {
                    $this->weeks[$week] = [];
                }
                $this->weeks[$week][$reportDay->getWeekDay()] =& $this->days[$yearDay];
            }

            // Register with the respective month
            $month = $reportDay->getMonth();
            if (!array_key_exists($month, $this->months)) {
                $this->months[$month] = [];
            }
            $this->months[$month][$reportDay->getDay()] =& $this->days[$yearDay];
        }
    }

    /**
     * Create a yearly report for a particular user
     *
     * @param User $user User
     * @param null $year Year
     * @return UserReport Yearly user report
     */
    public static function byYear(User $user, $year = null)
    {
        if ($year === null) {
            $year = intval(date('Y'));
        }
        $timezone = new \DateTimeZone(App::getConfig('common.timezone'));
        $from = new \DateTimeImmutable($year.'-01-01', $timezone);
        $to = new \DateTimeImmutable($year.'-12-31', $timezone);
        return new static($user, $from, $to);
    }

    /**
     * Return the report year
     *
     * @return int Report year
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Return the associated user
     *
     * @return User User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Return a day range
     *
     * @param int $yearDayStart Start day of the year
     * @param int $yearDayEnd End day of the year
     * @return array User day reports for the given range
     */
    public function getRange($yearDayStart, $yearDayEnd)
    {
        $dayIndices = array_flip(array_keys($this->days));
        if (array_key_exists($yearDayStart, $dayIndices) && ($yearDayEnd > $yearDayStart)) {
            return array_slice($this->days, $dayIndices[$yearDayStart], $yearDayEnd - $yearDayStart);
        }
        return array();
    }

    /**
     * Return the user's holiday entitlement
     *
     * @return int Holiday entitlement
     */
    public function getPersonalHolidays()
    {
        return $this->personalHolidays;
    }

    /**
     * Return the already planned holidays
     *
     * @return int Planned holidays
     */
    public function getPersonalHolidaysPlanned()
    {
        return $this->personalHolidaysPlanned;
    }

    /**
     * Return the past holidays
     *
     * @return int Past holidays
     */
    public function getPersonalHolidaysPast()
    {
        return $this->personalHolidaysPast;
    }
}
