<?php

/**
 * Toggl Dashboard
 *
 * @category    Apparat
 * @package     Apparat\Server
 * @subpackage  Tollwerk\Toggl\Application\Service
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

namespace Tollwerk\Toggl\Application\Service;

use Tollwerk\Toggl\Domain\Model\Day;
use Tollwerk\Toggl\Domain\Model\User;
use Tollwerk\Toggl\Domain\Repository\DayRepository;
use Tollwerk\Toggl\Ports\App;

/**
 * Statistics service
 *
 * @package Apparat\Server
 * @subpackage Tollwerk\Toggl\Application\Service
 */
class StatisticsService
{
    /**
     * Date statistics
     *
     * @var array
     */
    protected static $dateStatistics = [];

    /**
     * Return user statistics
     *
     * @param User $user User
     * @param null|int $year Year (default: current year)
     * @return array User statistics
     */
    public static function getUserStatistics(User $user, $year = null)
    {
        $year = ($year === null) ? date('Y') : intval($year);
        $dateStats = self::getDateStatistics($year);
        $timezone = new \DateTimeZone(App::getConfig('common.timezone'));
        $userHolidays = $user->getDays($year);
        $userStats = $user->getStats($year);
        $today = new \DateTimeImmutable('today', $timezone);

        $stats = array_merge(
            App::getConfig('common'),
            App::getConfig('user.'.$user->getToken()),
            [
                'user' => $user->getName(),
                'user_id' => $user->getId(),
                'holidays_taken' => 0,
                'personal_holidays' => [],
                'by_year' => [
                    'time' => 0,
                    'time_total' => 0,
                    'time_status' => 0,
                    'billable' => 0,
                    'billable_sum' => 0,
                    'costs_total' => 0,
                    'costs_status' => 0,
                ],
                'by_month' => [
                    'time' => [],
                    'time_total' => [],
                    'time_status' => [],
                    'billable' => [],
                    'billable_sum' => [],
                    'costs_total' => [],
                    'costs_status' => [],
                ],
                'by_week' => [
                    'time' => [],
                    'time_total' => [],
                    'time_status' => [],
                    'billable' => [],
                    'billable_sum' => [],
                    'costs_total' => [],
                    'costs_status' => [],
                ],
                'by_day' => [
                    'time' => [],
                    'time_total' => [],
                    'time_status' => [],
                    'billable' => [],
                    'billable_sum' => [],
                    'costs_total' => [],
                    'costs_status' => [],
                ],
            ],
            $dateStats
        );
        $stats['working_days'] = array_combine($stats['working_days'], $stats['working_days']);

        // Strip all user dependent non-working days
        for ($day = new \DateTime($year.'-01-01', $timezone), $weekday = $day->format('w');
             ($day->format('Y') == $year) && ($day->format('z') < $stats['total_days']);
             $day = $day->modify('+1 day'), $weekday = ($weekday + 1) % 7
        ) {
            // If this is not a user dependent working day
            if (!array_key_exists($weekday, $stats['working_days'])
                && array_key_exists($day->format('n'), $stats['workdays_by_month'])
                && array_key_exists($day->format('j'), $stats['workdays_by_month'][$day->format('n')])
            ) {
                unset($stats['workdays_by_month'][$day->format('n')][$day->format('j')]);
                --$stats['total_workdays'];
            }
        }

        // Run through all personal holidays
        foreach ($userHolidays as $userHoliday) {
            $holiday = $userHoliday->getDate();

            // If the holiday has already passed: Register
            if ($holiday < $today) {
                ++$stats['holidays_taken'];
            }

            // If the holiday is a working day
            if (array_key_exists($holiday->format('n'), $stats['workdays_by_month'])
                && array_key_exists($holiday->format('j'), $stats['workdays_by_month'][$holiday->format('n')])
            ) {
                $stats['personal_holidays'][$holiday->format('z')] = intval(ltrim($holiday->format('W'), '0'));
                unset($stats['workdays_by_month'][$holiday->format('n')][$holiday->format('j')]);
            }
        }

        // Calculate costs per day
        $userCostsPerMonth = floatval(App::getConfig('user.'.$user->getToken().'.costs'));
        $stats['by_year']['costs_total'] = $userCostsPerMonth * 12;
        $stats['by_month']['costs_total'] = array_fill_keys(array_keys($stats['workdays_by_month']),
            $userCostsPerMonth);

        // Clone the remaining working day structure for time entries
        foreach ($stats['workdays_by_month'] as $month => $workdays) {
            $userCostsPerDay = $userCostsPerMonth / count($workdays);

            $stats['by_year']['time_total'] +=
            $stats['by_month']['time_total'][$month] = count($workdays) * $stats['working_hours_per_day'] * 3600;

            $stats['by_month']['time'][$month] =
            $stats['by_month']['time_status'][$month] =
            $stats['by_month']['billable'][$month] =
            $stats['by_month']['billable_sum'][$month] =
            $stats['by_month']['costs_status'][$month] = 0;
            $stats['by_month']['costs_total'][$month] = count($workdays) * $userCostsPerDay;

            foreach (array_unique($workdays) as $week) {
                $stats['by_week']['time'][$week] =
                $stats['by_week']['time_total'][$week] =
                $stats['by_week']['time_status'][$week] =
                $stats['by_week']['billable'][$week] =
                $stats['by_week']['billable_sum'][$week] =
                $stats['by_week']['costs_status'][$week] =
                $stats['by_week']['costs_total'][$week] = 0;
            }
            foreach ($workdays as $week) {
                $stats['by_week']['time_total'][$week] += $stats['working_hours_per_day'] * 3600;
                $stats['by_week']['costs_total'][$week] += $userCostsPerDay;
            }

            $stats['by_day']['time'][$month] =
            $stats['by_day']['time_status'][$month] =
            $stats['by_day']['billable'][$month] =
            $stats['by_day']['billable_sum'][$month] =
            $stats['by_day']['costs_status'][$month] = array_fill_keys(array_keys($workdays), 0);
            $stats['by_day']['time_total'][$month] = array_fill_keys(array_keys($workdays),
                $stats['working_hours_per_day'] * 3600);
            $stats['by_day']['costs_total'][$month] = array_fill_keys(array_keys($workdays), $userCostsPerDay);
        }

        // Run through all user statistics
        foreach ($userStats as $userStat) {
            $entryDate = $userStat->getDate();
            $day = $entryDate->format('j');
            $month = $entryDate->format('n');
            $week = intval(ltrim($entryDate->format('W'), '0'));

            // Test if the record is for a regular working day
            $isWorkingDay = array_key_exists($month, $stats['workdays_by_month'])
                && array_key_exists($day, $stats['workdays_by_month'][$month]);

            // Create the necessary keys if it was not a working day
            if (!$isWorkingDay) {
                $stats['workdays_by_month'][$month][$day] = $week;
                $stats['by_day']['time'][$month][$day] =
                $stats['by_day']['billable'][$month][$day] =
                $stats['by_day']['billable_sum'][$month][$day] = 0;
            }

            $stats['by_year']['time'] += $userStat->getTotal();
            $stats['by_year']['time_status'] = $stats['by_year']['time'] / $stats['by_year']['time_total'];
            $stats['by_year']['billable'] += $userStat->getBillable();
            $stats['by_year']['billable_sum'] += $userStat->getBillableSum();
            $stats['by_year']['costs_status'] = $stats['by_year']['billable_sum'] / $stats['by_year']['costs_total'];

            $stats['by_month']['time'][$month] += $userStat->getTotal();
            $stats['by_month']['time_status'][$month] = $stats['by_month']['time'][$month] /
                $stats['by_month']['time_total'][$month];
            $stats['by_month']['billable'][$month] += $userStat->getBillable();
            $stats['by_month']['billable_sum'][$month] += $userStat->getBillableSum();
            $stats['by_month']['costs_status'][$month] = $stats['by_month']['billable_sum'][$month] /
                $stats['by_month']['costs_total'][$month];

            $stats['by_week']['time'][$week] += $userStat->getTotal();
            $stats['by_week']['time_status'][$week] = $stats['by_week']['time'][$week] /
                $stats['by_week']['time_total'][$week];
            $stats['by_week']['billable'][$week] += $userStat->getBillable();
            $stats['by_week']['billable_sum'][$week] += $userStat->getBillableSum();
            $stats['by_week']['costs_status'][$week] = $stats['by_week']['billable_sum'][$week] /
                $stats['by_week']['costs_total'][$week];

            $stats['by_day']['time'][$month][$day] += $userStat->getTotal();
            $stats['by_day']['time_status'][$month][$day] = $stats['by_day']['time'][$month][$day] /
                $stats['by_day']['time_total'][$month][$day];
            $stats['by_day']['billable'][$month][$day] += $userStat->getBillable();
            $stats['by_day']['billable_sum'][$month][$day] += $userStat->getBillableSum();
            $stats['by_day']['costs_status'][$month][$day] = $stats['by_day']['billable_sum'][$month][$day] /
                $stats['by_day']['costs_total'][$month][$day];
        }

        return $stats;
    }

    /**
     * Return date statistics
     *
     * @param null|int $year Year (default: current year)
     * @return array Date statistics
     */
    public static function getDateStatistics($year = null)
    {
        $year = ($year === null) ? date('Y') : intval($year);
        if (!array_key_exists($year, self::$dateStatistics)) {
            $stats = [
                'total_days' => date('z', mktime(0, 0, 0, 12, 31, $year)) + 1,
                'total_workdays' => 0,
                'workdays_by_month' => array_fill(1, 12, []),
                'business_holidays' => [],
            ];

            /** @var DayRepository $dayRepository */
            $dayRepository = App::getEntityManager()->getRepository('Tollwerk\Toggl\Domain\Model\Day');
            /** @var Day $businessHoliday */
            foreach ($dayRepository->getBusinessHolidays($year) as $businessHoliday) {
                $stats['business_holidays'][$businessHoliday->getDayOfYear()] = $businessHoliday->getName();#
            }

            // Create a list of all working days
            $timezone = new \DateTimeZone(App::getConfig('common.timezone'));
            for ($day = new \DateTime($year.'-01-01', $timezone), $weekday = $day->format('w');
                 ($day->format('Y') == $year) && ($day->format('z') < $stats['total_days']);
                 $day = $day->modify('+1 day'), $weekday = ($weekday + 1) % 7
            ) {
                // If this is not a weekend or a business holiday
                if (($weekday != 0) && ($weekday != 6)
                    && !array_key_exists($day->format('z'), $stats['business_holidays'])
                ) {
                    ++$stats['total_workdays'];
                    $stats['workdays_by_month'][$day->format('n')][$day->format('j')] =
                        intval(ltrim($day->format('W'), '0'));
                }
            }

            self::$dateStatistics[$year] = $stats;
        }

        return self::$dateStatistics[$year];
    }
}
