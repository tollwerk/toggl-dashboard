<?php

/**
 * Toggl Dashboard
 *
 * @category    Apparat
 * @package     Tollwerk\Toggl
 * @subpackage  Tollwerk\Toggl\Infrastructure\Processor
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

namespace Tollwerk\Toggl\Infrastructure\Processor;

use Tollwerk\Toggl\Domain\Model\Contract;
use Tollwerk\Toggl\Domain\Report\DayReport;
use Tollwerk\Toggl\Domain\Report\UserReport;
use Tollwerk\Toggl\Ports\App;

/**
 * Chart processor
 *
 * @package Tollwerk\Toggl
 * @subpackage Tollwerk\Toggl\Infrastructure
 */
class Chart
{
    /**
     * Border color
     *
     * @var string
     */
    const COLOR_BORDER = '#ffffff';
    /**
     * Weekend color
     *
     * @var string
     */
    const COLOR_WEEKEND = '#eeeeee';
    /**
     * Business holiday color (text)
     *
     * @var string
     */
    const COLOR_HOLIDAY = '#5eb1ff';
    /**
     * Business holiday color
     *
     * @var string
     */
    const COLOR_HOLIDAY_BUSINESS_BG = '#d2e9ff';
    /**
     * Personal holiday color
     *
     * @var string
     */
    const COLOR_HOLIDAY_PERSONAL_BG = '#d2e9ff';
    /**
     * Billable plot line color
     *
     * @var string
     */
    const COLOR_LINE_BILLABLE = '#009900';
    /**
     * Working hours plot line color
     *
     * @var string
     */
    const COLOR_LINE_WORKING = '#5fdd5f';
    /**
     * Minimum billable data color
     *
     * @var string
     */
    const COLOR_DATA_MIN = [255, 0, 0];
    /**
     * Maximum billable data color
     *
     * @var string
     */
    const COLOR_DATA_MAX = [0, 142, 0];
    /**
     * Average color
     *
     * @var string
     */
//    const COLOR_AVERAGE = [114, 0, 255];
    const COLOR_AVERAGE = [127, 127, 127];

    /**
     * Create the user time chart data
     *
     * @param UserReport $userReport User statistics data
     * @param \DateTime|\DateTimeInterface $datetime Timestamp
     * @param array $dailyUserBillableSums Daily user billable sums
     * @return array User time chart data
     * @see http://api.highcharts.com/highcharts
     * @see http://www.highcharts.com/demo/column-placement
     */
    public static function weekly(UserReport $userReport, \DateTime $datetime = null, array &$dailyUserBillableSums)
    {
        if ($datetime == null) {
            $datetime = new \DateTime('now');
        }

        // Determine the week start
        $today = new \DateTime('now');
        $weekDay = (intval($datetime->format('N')) + 7 - intval(App::getConfig('common.start_weekday'))) % 7;
        $currentDay = clone $datetime;
        $currentDay = $currentDay->setTime(0, 0, 0)->modify('-'.$weekDay.' days');
        $currentYearDay = $currentDay->format('z');

        /** @var DayReport[] $dayReports */
        $dayReports = $userReport->getRange($currentYearDay, $currentYearDay + 7);
        if (!count($dayReports)) {
            throw new \RuntimeException('Invalid date range');
        }

        // Determine the working hours per day
        $userWorkingHoursPerDay = null;
        $userMinBillableHoursPerDay = 0;
        foreach ($dayReports as $dayReport) {
            if (($userWorkingHoursPerDay === null) && ($dayReport->getContract() instanceof Contract)) {
                $userWorkingHoursPerDay = $dayReport->getContract()->getWorkingHoursPerDay();
            }
            if (!$userMinBillableHoursPerDay && ($dayReport->getContract() instanceof Contract)) {
                $userMinBillableHoursPerDay = $dayReport->getBillableTarget();
            }
        }
        if ($userWorkingHoursPerDay === null) {
            throw new \RuntimeException('No contracts');
        }
        $minYAxisRange = $userWorkingHoursPerDay;

        // Calculate day data
        $series = ['total' => [], 'billable' => []];
        $weekAverage = ['total' => [], 'billable' => [], 'time' => [], 'sum' => [], 'status' => []];
        $plotBands = [
            self::plotBand(4.5, 5.5, self::COLOR_WEEKEND),
            self::plotBand(5.5, 6.5, self::COLOR_WEEKEND),
        ];

        // Run through all week reports
        foreach ($dayReports as $index => $dayReport) {
            $dayTotal = $dayReport->getTimeActual() ? self::round($dayReport->getTimeActual() / 3600) : null;
            $dayBillableTime = $dayReport->getBillableActual() ? self::round($dayReport->getBillableActual() / 3600) : null;
            $dayRevenueStatus = $dayReport->getRevenueStatus() ?: null;
            $dayRevenueTime = $dayRevenueStatus ? self::round($dayReport->getBillableTarget() * $dayRevenueStatus) : null;

            // If this is a business holiday
            if ($businessHoliday = $dayReport->getBusinessHoliday()) {
                $plotBands[] = self::plotBand(
                    $index - .5, $index + .5, self::COLOR_HOLIDAY_BUSINESS_BG, self::COLOR_HOLIDAY,
                    $businessHoliday
                );

                // If this is a personal holiday
            } elseif ($dayReport->getPersonalHoliday()) {
                $plotBands[] = self::plotBand(
                    $index - .5, $index + .5, self::COLOR_HOLIDAY_PERSONAL_BG, self::COLOR_HOLIDAY,
                    self::personalHolidayLabel($dayReport)
                );
            }

            // Else if the day should be used for the week average
            if (($dayReport->getYearDay() <= $today->format('z')) && $dayReport->isWorkingDay()) {
                $weekAverage['total'][] = $dayTotal;
                $weekAverage['billable'][] = $dayRevenueTime;
                $weekAverage['time'][] = $dayBillableTime;
                $weekAverage['sum'][] = $dayReport->getRevenueSum();
                $weekAverage['status'][] = self::round($dayRevenueStatus * 100);
            }

            $series['total'][] = [
                'y' => $dayTotal,
                'color' => $dayReport->isWorkingDay() ? self::interpolateHex(
                    self::lighten(self::COLOR_DATA_MIN, .3),
                    self::lighten(self::COLOR_DATA_MAX, .3),
                    $dayReport->getTimeStatus()
                ) : self::rgbToHex(self::lighten(self::hexToRgb(self::COLOR_HOLIDAY), .5)),
            ];
            $series['billable'][] = [
                'y' => $dayRevenueTime,
                'billable' => [
                    'time' => $dayBillableTime,
                    'sum' => $dayReport->getRevenueSum(),
                    'status' => self::round($dayRevenueStatus * 100)
                ],
                'total' => $dayTotal,
                'color' => $dayReport->isWorkingDay() ? self::interpolateHex(
                    self::COLOR_DATA_MIN,
                    self::COLOR_DATA_MAX,
                    $dayReport->getRevenueStatus()
                ) : self::COLOR_HOLIDAY,
            ];
        }

        // Add the week averages
        if ($averageDays = count($weekAverage['total'])) {
            $totalAverage = self::round(array_sum($weekAverage['total']) / $averageDays);
            $series['total'][] = [
                'y' => $totalAverage,
                'color' => self::rgbToHex(self::lighten(self::COLOR_AVERAGE, .3)),
            ];

            $series['billable'][] = [
                'y' => self::round(array_sum($weekAverage['billable']) / $averageDays) ?: null,
                'billable' => [
                    'time' => self::round(array_sum($weekAverage['time']) / $averageDays) ?: null,
                    'sum' => self::round(array_sum($weekAverage['sum']) / $averageDays) ?: null,
                    'status' => self::round(array_sum($weekAverage['status']) / $averageDays) ?: 0,
                ],
                'total' => $totalAverage,
                'color' => self::rgbToHex(self::COLOR_AVERAGE),
            ];
        }

        // Calculate the monthly average
        $monthAverage = ['total' => [], 'billable' => [], 'time' => [], 'sum' => [], 'status' => []];
        $monthStart = clone $datetime;
        $monthStart = $monthStart->setDate($datetime->format('Y'), $datetime->format('n'), 1)->setTime(0, 0, 0);
        /** @var DayReport[] $monthlyDayReports */
        $monthlyDayReports = $userReport->getRange($monthStart->format('z'),
            $monthStart->format('z') + $monthStart->format('t'));
        if (!count($monthlyDayReports)) {
            throw new \RuntimeException('Invalid date range');
        }
        foreach ($monthlyDayReports as $dayReport) {
            // If the day should be used for the month average
            if (($dayReport->getYearDay() <= $today->format('z')) && $dayReport->isWorkingDay() && !$dayReport->isHoliday()) {
                $dayTotal = $dayReport->getTimeActual() ? self::round($dayReport->getTimeActual() / 3600) : null;
                $dayBillableTime = $dayReport->getBillableActual() ? self::round($dayReport->getBillableActual() / 3600) : null;
                $dayRevenueStatus = $dayReport->getRevenueStatus() ?: null;
                $dayRevenueTime = $dayRevenueStatus ? self::round($dayReport->getBillableTarget() * $dayRevenueStatus) : null;

                $monthAverage['total'][] = $dayTotal;
                $monthAverage['billable'][] = $dayRevenueTime;
                $monthAverage['time'][] = $dayBillableTime;
                $monthAverage['sum'][] = $dayReport->getRevenueSum();
                $monthAverage['status'][] = self::round($dayRevenueStatus * 100);
            }

            // Add to the daily billable sum
            $monthDay = $dayReport->getDay();
            if (!array_key_exists($monthDay, $dailyUserBillableSums)) {
                $dailyUserBillableSums[$monthDay] = $dayReport->getRevenueSum();
            } else {
                $dailyUserBillableSums[$monthDay] += $dayReport->getRevenueSum();
            }
        }
        // Add the month averages
        if ($averageDays = count($monthAverage['total'])) {
            $totalAverage = self::round(array_sum($monthAverage['total']) / $averageDays);
            $series['total'][] = [
                'y' => $totalAverage,
                'color' => self::rgbToHex(self::lighten(self::COLOR_AVERAGE, .3)),
            ];

            $series['billable'][] = [
                'y' => self::round(array_sum($monthAverage['billable']) / $averageDays) ?: null,
                'billable' => [
                    'time' => self::round(array_sum($monthAverage['time']) / $averageDays) ?: null,
                    'sum' => self::round(array_sum($monthAverage['sum']) / $averageDays) ?: null,
                    'status' => self::round(array_sum($monthAverage['status']) / $averageDays) ?: 0,
                ],
                'total' => $totalAverage,
                'color' => self::rgbToHex(self::COLOR_AVERAGE),
            ];
        }

        // Construct the chart data
        $chart = [
            'chart' => ['type' => 'bar'],
            'title' => ['text' => null],
            'xAxis' => [
                'categories' => [
                    _('weekday.1.abbr'),
                    _('weekday.2.abbr'),
                    _('weekday.3.abbr'),
                    _('weekday.4.abbr'),
                    _('weekday.5.abbr'),
                    _('weekday.6.abbr'),
                    _('weekday.0.abbr'),
                    _('week.abbr'),
                    _('month.abbr'),
                ],
                'plotBands' => $plotBands
            ],
            'yAxis' => [
                'min' => 0,
                'minRange' => $minYAxisRange,
                'plotLines' => [
                    [ // Working hours threshold
                        'color' => self::COLOR_LINE_WORKING,
                        'width' => 1,
                        'value' => $userWorkingHoursPerDay,
                        'zIndex' => 100
                    ],
                    [ // Break even threshold
                        'color' => self::COLOR_LINE_BILLABLE,
                        'width' => 1,
                        'value' => $userMinBillableHoursPerDay,
                        'zIndex' => 100
                    ]
                ],
                'title' => [
                    'text' => '',
                ]
            ],
            'plotOptions' => [
                'bar' => [
                    'groupPadding' => 0,
                    'pointPadding' => 0,
                    'grouping' => false,

                ],
            ],
            'series' => [
                [
                    'name' => _('chart.total'),
                    'data' => $series['total'],
                    'dataLabels' => [
                        'enabled' => true,
                        'format' => sprintf(_('unit.hours'), '{y}'),
                        'style' => [
                            'color' => 'contrast',
                            'fontWeight' => 'normal',
                            'textShadow' => 'none',
                        ]
                    ],
                    'tooltip' => [
                        'pointFormat' => '<span style="color:{point.color}">●</span> {series.name}: <b>{point.y}h</b><br/>'
                    ]
                ],
                [
                    'name' => _('chart.billable'),
                    'data' => $series['billable'],
                    'dataLabels' => [
                        'enabled' => true,
                        'align' => 'right',
                        'inside' => true,
                        'formatter' => '%performance%',
                        'style' => [
                            'color' => 'contrast',
                            'fontWeight' => 'normal',
                            'textShadow' => 'none', // 0 0 6px contrast, 0 0 3px contrast
                        ]
                    ],
                    'tooltip' => [
//                        'pointFormat' => '<span style="color:{point.color}">●</span> {series.name}: <b>{point.y}h</b><br/>',
                        'pointFormatter' => '%billables%'
                    ]
                ],
            ],
            'credits' => [
                'enabled' => false
            ],
            'legend' => [
                'enabled' => false
            ]
        ];
        return $chart;
    }

    /**
     * Create the team chart
     *
     * @param \DateTimeInterface $datetime Current date
     * @param float $userCosts User costs
     * @param array $dailyUserBillableSums User daily billable sums
     * @return array
     */
    public static function team(\DateTimeInterface $datetime, $userCosts, array $dailyUserBillableSums)
    {
        $datetime = $datetime->setDate($datetime->format('Y'), $datetime->format('n'), 1);

        // Calculate the monthly system costs
        $monthlySystemCosts = $userCosts;
        $systemCosts = App::getConfig('system.costs');

        if (array_key_exists('yearly', $systemCosts)) {
            $monthlySystemCosts += array_sum(array_map('floatval', (array)$systemCosts['yearly'])) / 12;
        }
        if (array_key_exists('quarterly', $systemCosts)) {
            $monthlySystemCosts += array_sum(array_map('floatval', (array)$systemCosts['quarterly'])) / 4;
        }
        if (array_key_exists('monthly', $systemCosts)) {
            $monthlySystemCosts += array_sum(array_map('floatval', (array)$systemCosts['monthly']));
        }
        $monthlySystemCosts = round($monthlySystemCosts);

        // Initialize variables
        $pointStart = $datetime->format('U') * 1000 - 86400000;
        $currentMonth = $datetime->format('n');
        $currentYear = $datetime->format('Y');
        $workingDays = intval(App::getConfig('system.working_days'));

        // Collect all working days
        $workingDayCosts = [['y' => 0]];
        $cumulatedBillableSums = [['y' => null]];
        while ($datetime->format('n') == $currentMonth) {
            $weekDay = intval($datetime->format('w'));
            $weekDayBit = pow(2, $weekDay);
            $workingDayCosts[] = ($weekDayBit & $workingDays) ? 1 : null;
            $datetime = $datetime->modify('+1 day');
        }
        $monthWorkingDays = array_sum($workingDayCosts);
        $cumulatedCosts = 0;
        $cumulatedBillableSum = 0;
        $plotBands = [];

        // Run through all days of the month
        foreach ($workingDayCosts as $workingDay => $costs) {

            // Add a plot line if this is not a working day
            if ($costs === null) {
                $from = gmmktime(0, 0, 0, $currentMonth, $workingDay + 1, $currentYear) * 1000 - 43200000;
                $plotBands[] = self::plotBand($from, $from + 86400000, self::COLOR_WEEKEND);

                // Else: Add the costs data
            } else {
                $workingDayCosts[$workingDay] = [
                    'y' => round($cumulatedCosts),
                    'marker' => [
                        'enabled' => false
                    ],
                    'color' => self::rgbToHex(self::COLOR_DATA_MAX),
                ];
                $cumulatedCosts += $monthlySystemCosts / $monthWorkingDays;
            }

            // Cumulate and register the billable sum
            $dailyUserBillableSum = empty($dailyUserBillableSums[$workingDay + 1]) ? 0 : $dailyUserBillableSums[$workingDay + 1];
            $cumulatedBillableSum += $dailyUserBillableSum;
            $cumulatedBillableSums[] = [
                'y' => $dailyUserBillableSum ? round($cumulatedBillableSum) : null,
                'color' => self::rgbToHex(self::lighten(($cumulatedBillableSum >= $workingDayCosts[$workingDay]['y']) ?
                    self::COLOR_DATA_MAX : self::COLOR_DATA_MIN, .3))
            ];
        }

        $chart = [
            'title' => ['text' => null],
            'xAxis' => [
                'type' => 'datetime',
                'maxZoom' => 48 * 3600 * 1000,
                'plotBands' => $plotBands
            ],
            'yAxis' => [
                'title' => [
                    'text' => '',
                ]
            ],
            'series' => [
                [
                    'type' => 'column',
                    'data' => $cumulatedBillableSums,
                    'pointStart' => $pointStart,
                    'pointInterval' => 24 * 3600 * 1000,
                    'groupPadding' => 0,
                ],
                [
                    'type' => 'line',
                    'data' => $workingDayCosts,
                    'pointStart' => $pointStart,
                    'pointInterval' => 24 * 3600 * 1000, // one day
                    'lineWidth' => 1,
                ],
            ],
            'credits' => [
                'enabled' => false
            ],
            'legend' => [
                'enabled' => false
            ]
        ];
        return $chart;
    }

    /**
     * Round a floating point number
     *
     * @param float $number Floating point number
     * @param int $decimals Decimals
     * @return string Rounded number
     */
    protected static function round($number, $decimals = 2)
    {
        if ($number == 0) {
            return null;
        }
        return floatval(number_format($number, $decimals));
    }

    /**
     * Create a plot band
     *
     * @param float $from From value
     * @param float $to To value
     * @param string $backgroundColor Background color
     * @param string $textColor Text color
     * @param string $label Label
     * @return array Plot band
     */
    protected static function plotBand($from, $to, $backgroundColor, $textColor = null, $label = null)
    {
        $plotBand = [
            'color' => $backgroundColor,
            'from' => $from,
            'to' => $to,
            'borderColor' => self::COLOR_BORDER,
            'borderWidth' => 1,
        ];
        if ($label !== null) {
            $plotBand['label'] = [
                'text' => $label,
                'align' => 'center',
                'verticalAlign' => 'middle',
                'style' => ['color' => $textColor],
            ];
        }
        return $plotBand;
    }

    /**
     * Interpolate two RGB colors and return as hex color
     *
     * @param array $min Minimum color
     * @param array $max Maximum color
     * @param float $status Status
     * @return string Hex color
     */
    protected static function interpolateHex(array $min, array $max, $status)
    {
        $status = max(0, min(1, $status));
        $rgb = [];
        for ($color = 0; $color < 3; ++$color) {
            $rgb[$color] = $min[$color] + $status * ($max[$color] - $min[$color]);
        }
        return self::rgbToHex($rgb);
    }

    /**
     * Convert an RGB color to a hex value
     *
     * @param array $rgb RGB color
     * @return string Hex color
     */
    protected static function rgbToHex($rgb)
    {
        $hex = "#";
        $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

        return $hex; // returns the hex value including the number sign (#)
    }

    /**
     * Convert a hex color to RGB values
     *
     * @param string $hex Hex color
     * @return array RGB color
     */
    protected static function hexToRgb($hex)
    {
        return sscanf($hex, "#%02x%02x%02x");
    }

    /**
     * Lighten an RGB color
     *
     * @param array $rgb RGB color
     * @param int $factor Lighten factor
     * @return array Lightened RGB color
     */
    protected static function lighten(array $rgb, $factor = 1)
    {
        $factor = max(0, min(1, $factor));
        for ($color = 0; $color < 3; ++$color) {
            $rgb[$color] = round(255 - $factor * (255 - $rgb[$color]));
        }
        return $rgb;
    }

    /**
     * Return the label for a personal holiday
     *
     * @param DayReport $dayReport Day report
     * @return string Personal holiday label
     */
    protected static function personalHolidayLabel(DayReport $dayReport)
    {
        $personalHoliday = $dayReport->getPersonalHoliday();
        if ($personalHoliday === DayReport::DEFAULT_HOLIDAY) {
            return _('holiday.personal');
        }
        if ($dayReport->isExcused()) {
            return _('holiday.personal.excused');
        }
        if ($dayReport->isOvertime()) {
            return _('holiday.personal.overtime');
        }
        return strval($personalHoliday);
    }
}
