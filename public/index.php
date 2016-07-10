<?php

/**
 * Toggl Dashboard
 *
 * @category    Tollwerk
 * @package     Tollwerk\Toggl
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

namespace Tollwerk\Dashboard;

use Tollwerk\Toggl\Domain\Model\User;
use Tollwerk\Toggl\Domain\Report\UserReport;
use Tollwerk\Toggl\Infrastructure\Processor\Chart;
use Tollwerk\Toggl\Infrastructure\Renderer\Html;

//header('Content-Type: text/plain');
//header('Content-Type: application/json');
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'bootstrap.php';

$entityManager = \Tollwerk\Toggl\Ports\App::getEntityManager();
$userRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\User');
/** @var User[] $users */
$users = [];
/** @var User $user */
foreach ($userRepository->findBy(['active' => true/*, 'id' => 7*/]) as $user) {
    $users[$user->getId()] = $user;
}
$userReports = [];

// Run through all users
/** @var User $user */
foreach ($users as $user) {
    try {
//        header('content-type: text/plain');
        $userReports[$user->getId()] = UserReport::byYear($user);
    } catch (\InvalidArgumentException $e) {
        continue;
    }
}

// Determine the first monday of the year
$firstMondayOfYear = new \DateTime('@'.mktime(0, 0, 0, 1, 1));
$firstMondayOfYear = $firstMondayOfYear->modify('+'.((8 - $firstMondayOfYear->format('w')) % 7).' days');

// Determine the current calendar week
$currentCalendarWeek = empty($_GET['cw']) ? intval(ltrim((new \DateTimeImmutable('now'))->format('W'),
    '0')) : intval($_GET['cw']);
$currentCalendarWeekStart = clone $firstMondayOfYear;
$currentCalendarWeekStart = $currentCalendarWeekStart->modify('+'.($currentCalendarWeek - 1).' weeks');
$currentCalendarWeekEnd = clone $currentCalendarWeekStart;
$currentCalendarWeekEnd = $currentCalendarWeekEnd->modify('+6 days');

$calendarWeekStartFormat = 'j.';
if ($currentCalendarWeekStart->format('n') != $currentCalendarWeekEnd->format('n')) {
    $calendarWeekStartFormat .= 'n.';

    if ($currentCalendarWeekStart->format('Y') != $currentCalendarWeekEnd->format('Y')) {
        $calendarWeekStartFormat .= 'Y';
    }
}
$dateStr = $currentCalendarWeekStart->format($calendarWeekStartFormat).'-'.$currentCalendarWeekEnd->format('j.n.Y');

?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="900; URL=index.php">
    <title><?= sprintf(_('header.calendarweek'), $dateStr, $currentCalendarWeek); ?> — Toggl Dashboard</title>
    <script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/no-data-to-display.js"></script>
    <script src="/js/dashboard.js"></script>
    <link href="/css/dashboard.css" type="text/css" rel="stylesheet"/>
</head>
<body>
<h1><?= sprintf(_('header.calendarweek'), $dateStr, $currentCalendarWeek); ?></h1>
<nav class="weeks"><?php
    for ($monday = clone $firstMondayOfYear; $monday->format('Y') == date('Y'); $monday = $monday->modify('+1 week')):
        $week = ltrim($monday->format('W'), '0');
        ?><a href="index.php?cw=<?= $week; ?>"<?php if ($week == $currentCalendarWeek) {
        echo ' class="current"';
    } ?>><?= $week; ?></a> <?php
    endfor;
    ?></nav>
<div class="time-charts"><?php
    $monthlyUserCosts = 0;
    $dailyUserBillableSums = [];

    // Run through all user statistics
    /**
     * @var int $userId
     * @var UserReport $userReport
     */
    foreach ($userReports as $userId => $userReport):
        $monthlyUserCosts += $userReport->getMonthlyCosts($currentCalendarWeekStart->format('n'));

        $userChart = Html::json(Chart::weekly($userReport, $currentCalendarWeekStart, $dailyUserBillableSums));
        $userChart = preg_replace('/"%([^%]+)%"/', 'Tollwerk.Dashboard.$1', $userChart);
        $userOvertime = number_format($userReport->getUser()->getOvertime(), 2);
        $userOvertimeClass = ($userReport->getUser()->getOvertime() >= 0) ? 'positive' : 'negative';
        $userRemainingHolidays = $userReport->getPersonalHolidays() - $userReport->getPersonalHolidaysPlanned();
        $userRemainingHolidaysClass = ($userRemainingHolidays >= 0) ? 'positive' : 'negative';

        ?>
        <figure class="time-chart">
        <figcaption><?= Html::h($userReport->getUser()->getName()); ?></figcaption>
        <div id="time-chart-<?= $userReport->getUser()->getToken(); ?>" style="width:320px;height:260px">
            <script>Tollwerk.Dashboard.initUserTimeChart('time-chart-<?= $userReport->getUser()->getToken(); ?>', <?= $userChart; ?>);</script>
        </div>
        <dl>
            <dt><?= _('info.overtime'); ?></dt>
            <dd class="<?= $userOvertimeClass; ?>"><?= Html::h($userOvertime); ?></dd>
            <dt><?= sprintf(_('info.holiday'), $currentCalendarWeekStart->format('Y')); ?></dt>
            <dd><?= $userReport->getPersonalHolidays(); ?></dd>
            <dt><?= sprintf(_('info.holiday.taken'), $currentCalendarWeekStart->format('Y')); ?></dt>
            <dd><?= $userReport->getPersonalHolidaysPast(); ?></dd>
            <dt><?= sprintf(_('info.holiday.remaining'), $currentCalendarWeekStart->format('Y')); ?></dt>
            <dd class="<?= $userRemainingHolidaysClass; ?>"><?= $userRemainingHolidays; ?></dd>
        </dl>
        </figure><?php

    endforeach;

    $teamChart = Html::json(Chart::team($currentCalendarWeekStart, $monthlyUserCosts, $dailyUserBillableSums));

    ?>
    <figure class="time-chart">
        <figcaption><?= Html::h(sprintf(_('header.team'), strftime('%B', $currentCalendarWeekStart->format('U')))); ?></figcaption>
        <div id="time-chart-team" style="width:320px;height:260px">
            <script>Tollwerk.Dashboard.initUserTimeChart('time-chart-team', <?= $teamChart; ?>);</script>
        </div>
    </figure><?php

    ?></div>
</body>
</html>
