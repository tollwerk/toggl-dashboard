<?php

/**
 * Toggl Dashboard
 *
 * @category    Apparat
 * @package     Apparat\Server
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

use Tollwerk\Toggl\Ports\App;

/**
 * Chart processor
 *
 * @package Apparat\Server
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
    const COLOR_HOLIDAY_BUSINESS_BG = '#c1e1ff';
    /**
     * Personal holiday color
     *
     * @var string
     */
    const COLOR_HOLIDAY_PERSONAL_BG = '#c1e1ff';
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
     * @param array $data User statistics data
     * @param \DateTimeInterface $datetime Timestamp
     * @return array User time chart data
     * @see http://api.highcharts.com/highcharts
     * @see http://www.highcharts.com/demo/column-placement
     */
    public static function createUserWeekChart(array $data, \DateTime $datetime = null)
    {
        if ($datetime == null) {
            $datetime = new \DateTime('now');
        }

        // Determine the week start
        $today = new \DateTime('now');
        $weekDay = ($datetime->format('N') + 7 - intval(App::getConfig('common.start_weekday'))) % 7;
        $currentDay = clone $datetime;
        $currentDay = $currentDay->setTime(0, 0, 0)->modify('-'.$weekDay.' days');
        $month = $currentDay->format('n');

        $userWorkingHoursPerDay = $data['working_hours_per_day'];
        $minYAxisRange = $userWorkingHoursPerDay;

        // The minimum billable hours per day
        $userMinBillableHoursPerDay = $data['by_day']['costs_total'][$currentDay->format('n')][$currentDay->format('j')]
            / $data['rate'];

        // Colors
        $weekendBackgroundColor = '#eeeeee';

        // Calculate day data
        $series = ['total' => [], 'billable' => []];
        $plotBands = [
            self::plotBand(4.5, 5.5, $weekendBackgroundColor),
            self::plotBand(5.5, 6.5, $weekendBackgroundColor),
        ];
        $weekAverage = ['total' => [], 'billable' => [], 'billable_status' => []];
        $monthAverage = ['total' => [], 'billable' => [], 'billable_status' => []];

        // Run through all the workdays since the first of the month until this week
        $monthDay = clone $datetime;
        for ($monthDay->setDate(date('Y'), date('n'), 1); $monthDay < $currentDay; $monthDay->modify('+1 day')) {
            $yearDay = $monthDay->format('z');
            $month = $monthDay->format('n');
            $day = $monthDay->format('j');
            $weekDay = $monthDay->format('w');

            if (!array_key_exists($yearDay, $data['business_holidays'])
                && !array_key_exists($yearDay, $data['personal_holidays'])
                && ($weekDay > 0)
                && ($weekDay < 6)
            ) {
                $dayTotal = floatval(self::round($data['by_day']['time'][$month][$day] / 3600));
                $dayBillable = floatval(self::round($data['by_day']['billable'][$month][$day] / 3600));
                $monthAverage['total'][] = $dayTotal;
                $monthAverage['billable'][] = $dayBillable;
                $monthAverage['billable_status'][] = $data['by_day']['costs_status'][$month][$day];
            }
        }

        // Run through all days of the week
        for ($index = 0; $index < 7; ++$index, $currentDay->modify('+1 day')) {
            $yearDay = $currentDay->format('z');
            $month = $currentDay->format('n');
            $day = $currentDay->format('j');
            $dayTotal = self::round($data['by_day']['time'][$month][$day] / 3600);
            $dayBillable = self::round($data['by_day']['billable'][$month][$day] / 3600);
            $series['total'][] = [
                'y' => $dayTotal,
                'color' => self::interpolateHex(
                    self::lighten(self::COLOR_DATA_MIN, .3),
                    self::lighten(self::COLOR_DATA_MAX, .3),
                    $data['by_day']['time_status'][$month][$day]
                ),
            ];
            $series['billable'][] = [
                'y' => $dayBillable,
                'total' => $dayTotal,
                'color' => self::interpolateHex(
                    self::COLOR_DATA_MIN,
                    self::COLOR_DATA_MAX,
                    $data['by_day']['costs_status'][$month][$day]
                ),
            ];

            // If this is a business holiday
            if (array_key_exists($yearDay, $data['business_holidays'])) {
                $plotBands[] = self::plotBand(
                    $index - .5, $index + .5, self::COLOR_HOLIDAY_BUSINESS_BG, self::COLOR_HOLIDAY,
                    $data['business_holidays'][$yearDay]
                );

                // If this is a personal holiday
            } elseif (array_key_exists($yearDay, $data['personal_holidays'])) {
                $plotBands[] = self::plotBand(
                    $index - .5, $index + .5, self::COLOR_HOLIDAY_PERSONAL_BG, self::COLOR_HOLIDAY,
                    _('holiday.personal')
                );

                // Else if the day should be used for the week average
            } elseif (($currentDay->format('z') <= $today->format('z')) && ($index < 6)) {
                $monthAverage['total'][] = $weekAverage['total'][] = $dayTotal;
                $monthAverage['billable'][] = $weekAverage['billable'][] = $dayBillable;
                $monthAverage['billable_status'][] = $weekAverage['billable_status'][] = $data['by_day']['costs_status'][$month][$day];
            }
        }

        // Run through the rest of the month
        for ($yearDay = $currentDay->format('z'); ($currentDay->format('n') == $month) && ($yearDay <= $today->format('z')); $currentDay->modify('+1 day')) {
            $yearDay = $currentDay->format('z');
            $month = $currentDay->format('n');
            $day = $currentDay->format('j');
            $weekDay = $currentDay->format('w');

            if (!array_key_exists($yearDay, $data['business_holidays'])
                && !array_key_exists($yearDay, $data['personal_holidays'])
                && ($weekDay > 0)
                && ($weekDay < 6)
            ) {
                $dayTotal = floatval(self::round($data['by_day']['time'][$month][$day] / 3600));
                $dayBillable = floatval(self::round($data['by_day']['billable'][$month][$day] / 3600));
                $monthAverage['total'][] = $dayTotal;
                $monthAverage['billable'][] = $dayBillable;
                $monthAverage['billable_status'][] = $data['by_day']['costs_status'][$month][$day];
            }
        }

//        print_r($monthAverage);

        // Add the week averages
        if (count($weekAverage['total'])) {
            $totalAverage = self::round(array_sum($weekAverage['total']) / count($weekAverage['total']));
            $totalStatus = $totalAverage / $data['working_hours_per_day'];
            $series['total'][] = [
                'y' => $totalAverage,
                'color' => self::rgbToHex(self::lighten(self::COLOR_AVERAGE, .3)),
            ];

            $billableAverage = self::round(array_sum($weekAverage['billable']) / count($weekAverage['billable']));
            $billableStatus = array_sum($weekAverage['billable_status']) / count($weekAverage['billable_status']);
            $series['billable'][] = [
                'y' => $billableAverage,
                'total' => $totalAverage,
                'color' => self::rgbToHex(self::COLOR_AVERAGE),
            ];
        }

        // Add the month averages
        if (count($monthAverage['total'])) {
            $totalAverage = self::round(array_sum($monthAverage['total']) / count($monthAverage['total']));
            $totalStatus = $totalAverage / $data['working_hours_per_day'];
            $series['total'][] = [
                'y' => $totalAverage,
                'color' => self::rgbToHex(self::lighten(self::COLOR_AVERAGE, .3)),
            ];

            $billableAverage = self::round(array_sum($monthAverage['billable']) / count($monthAverage['billable']));
            $billableStatus = array_sum($monthAverage['billable_status']) / count($monthAverage['billable_status']);
            $series['billable'][] = [
                'y' => $billableAverage,
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
                        'pointFormat' => '<span style="color:{point.color}">●</span> {series.name}: <b>{point.y}h</b><br/>'
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
}
