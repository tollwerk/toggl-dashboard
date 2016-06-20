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

/**
 * Chart processor
 *
 * @package Apparat\Server
 * @subpackage Tollwerk\Toggl\Infrastructure
 */
class Chart
{
    /**
     * Create the user time chart data
     *
     * @param array $data User statistics data
     * @return array User time chart data
     * @see http://api.highcharts.com/highcharts
     * @see http://www.highcharts.com/demo/column-placement
     */
    public static function createUserTimeChart(array $data)
    {
        $chart = [
            'chart' => ['type' => 'bar'],
            'title' => ['text' => null],
            'xAxis' => [
                'categories' => [
                    _('weekday.6.abbr'),
                    _('weekday.0.abbr'),
                    _('weekday.1.abbr'),
                    _('weekday.2.abbr'),
                    _('weekday.3.abbr'),
                    _('weekday.4.abbr'),
                    _('weekday.5.abbr'),
                ],
            ],
            'yAxis' => [
                'min' => 0,
                'plotLines' => [
                    [ // Working hours threshold
                        'color' => '#0000ff',
                        'width' =>  1,
                        'value' => 6,
                        'zIndex' => 100
                    ],
                    [ // Break even threshold
                        'color' => '#ff0000',
                        'width' =>  1,
                        'value' => 3,
                        'zIndex' => 100
                    ]
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
                    'data' => [7.3, 6, 5, 4, 3, 2, 1],
                    'dataLabels' => [
                        'enabled' => true,
                        'style' => [
                            'color' => 'contrast',
                            'fontWeight' => 'normal',
                            'textShadow' => '0 0 6px contrast, 0 0 3px contrast',
                        ]
                    ]
                ],
                [
                    'name' => _('chart.billable'),
                    'data' => [1, 2, 3, 4, 5, 6, 7],
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
}
