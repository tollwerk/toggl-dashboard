<?php

/**
 * Toggl Dashboard
 *
 * @category    Apparat
 * @package     Apparat\Server
 * @subpackage  ${NAMESPACE}
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

use Tollwerk\Toggl\Domain\Model\Stats;
use Tollwerk\Toggl\Domain\Model\User;
use Tollwerk\Toggl\Ports\App;

require_once __DIR__.DIRECTORY_SEPARATOR.'bootstrap.php';

$entityManager = App::getEntityManager();
$userRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\User');
$statsRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\Stats');
$dayRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\Day');
$timezone = new \DateTimeZone(App::getConfig('common.timezone'));
$today = new \DateTimeImmutable('today', $timezone);

// Run through all users
/** @var User $user */
foreach ($userRepository->findAll() as $user) {
    try {
        echo 'Updating overtimes of '.$user->getName().PHP_EOL;

        $userConfig = array_merge(App::getConfig('common'), App::getConfig('user.'.$user->getToken()));
        $userWorkingDays = array_combine($userConfig['working_days'], $userConfig['working_days']);
        $userWorkingHoursPerDay = floatval($userConfig['working_hours_per_day']);
        $userWorkingTimeBalance = $user->getOvertimeOffset();

        $userOvertimeDate = $user->getOvertimeDate();
        while ($userOvertimeDate <= $today) {

            // If this is a working day for the user
            if (array_key_exists($userOvertimeDate->format('w'), $userWorkingDays)
                && !$dayRepository->isUserHoliday($user, $userOvertimeDate)
            ) {
                $userWorkingTimeBalance -= $userWorkingHoursPerDay;
            }

            $userStats = $user->getDateStats($userOvertimeDate);
            if ($userStats instanceof Stats) {
                $userWorkingTimeBalance += $userStats->getTotal() / 3600;
            }

            echo 'Working time balance of '.$user->getName().' for '.$userOvertimeDate->format('Y-m-d').': '.$userWorkingTimeBalance.PHP_EOL;

            $userOvertimeDate = $userOvertimeDate->modify('+1 day');
        }

        $user->setOvertime($userWorkingTimeBalance);

    } catch (\InvalidArgumentException $e) {
        continue;
    }
}

$entityManager->flush();
