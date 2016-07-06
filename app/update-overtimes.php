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

use Tollwerk\Toggl\Domain\Model\Contract;
use Tollwerk\Toggl\Domain\Model\User;
use Tollwerk\Toggl\Domain\Report\DayReport;
use Tollwerk\Toggl\Domain\Report\UserReport;
use Tollwerk\Toggl\Ports\App;

require_once __DIR__.DIRECTORY_SEPARATOR.'bootstrap.php';

$entityManager = App::getEntityManager();
$userRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\User');
$statsRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\Stats');
$dayRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\Day');
$timezone = new \DateTimeZone(App::getConfig('common.timezone'));
$today = new \DateTimeImmutable('today', $timezone);

/**
 * Update the overtime balance for a particular user and a particular year
 *
 * @param User $user User
 * @param Contract $contract Effective user contract
 * @param int $year Year
 * @param float $overtimeBalance Overtime balance offset
 * @return float Overtime balance
 */
function updateYearlyUserOvertime(User $user, Contract $contract, $year, $overtimeBalance)
{
    // Get a user report for the year
    $userReport = UserReport::byYear($user, $year);
    $startDay = ($contract->getDate()->format('Y') == $year) ? $contract->getDate()->format('z') : 0;
    $endDay = (date('Y') == $year) ?

        // "now" should be "tomorrow" to have realtime calculations
        (new \DateTimeImmutable('now', $GLOBALS['timezone']))->format('z') :
        (365 + intval((new \DateTimeImmutable($year.'-01-01', $GLOBALS['timezone']))->format('L')));

    // Run through the relevant user report range
    /** @var DayReport $dayReport */
    foreach ($userReport->getRange($startDay, $endDay) as $dayReport) {

        // If this is a working day (and not a true holiday) for the user
        if ($dayReport->isWorkingDay() && !$dayReport->isHoliday(true) && !$dayReport->isExcused()) {
            // Subtract the daily working hours
            $overtimeBalance -= $contract->getWorkingHoursPerDay();
        }

        // Add the captured time
        $overtimeBalance += $dayReport->getTimeActual() / 3600;
    }

    return $overtimeBalance;
}

// Run through all users
/** @var User $user */
foreach ($userRepository->findBy(['active' => 1]) as $user) {
    try {
        echo 'Updating overtimes of '.$user->getName().PHP_EOL;

        // Find the latest user contract
        $userContract = $user->getEffectiveContract();
        if ($userContract instanceof Contract) {
            $userWorkingTimeBalance = $userContract->getOvertimeOffset();

            // Run through all contract years
            for ($year = $userContract->getDate()->format('Y'); $year <= date('Y'); ++$year) {
                $userWorkingTimeBalance = updateYearlyUserOvertime($user, $userContract, $year, $userWorkingTimeBalance);
            }

            echo 'Working time balance of '.$user->getName().': '.$userWorkingTimeBalance.PHP_EOL;
            $user->setOvertime($userWorkingTimeBalance);

        } else {
            echo 'No effective contract for '.$user->getName().', skipping ...'.PHP_EOL;
        }

    } catch (\InvalidArgumentException $e) {
        continue;
    }
}

$entityManager->flush();
