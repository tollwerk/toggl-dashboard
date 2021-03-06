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
use Tollwerk\Toggl\Domain\Repository\UserRepository;
use Tollwerk\Toggl\Ports\App;

require_once __DIR__.DIRECTORY_SEPARATOR.'bootstrap.php';

$entityManager = App::getEntityManager();
/** @var UserRepository $userRepository */
$userRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\User');
$statsRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\Stats');
$timezone = new \DateTimeZone(App::getConfig('common.timezone'));

$togglReportsClient = App::getTogglReportsClient();
$workspaces = App::getConfig('toggl.workspaces');
$dayStart = (new \DateTimeImmutable('today', $timezone))->modify('-1 week');
$dayEnd = (new \DateTimeImmutable('tomorrow', $timezone))->modify('-1 second')->modify('-1 week');

// Collect the user IDs to query
$userIds = $userRepository->findToggleIds();

// Run through all workspaces
foreach ($workspaces as $workspace) {

    // Run through one week
    for ($day = 0; $day < 8; ++$day) {
        // Request a summary report
        $report = $togglReportsClient->summary([
            'user_agent' => 'Tollwerk Toggl Dashboard',
            'workspace_id' => $workspace,
            'since' => $dayStart->format('c'),
            'until' => $dayEnd->format('c'),
            'user_ids' => implode(',', $userIds),
            'distinct_rates' => 'on',
            'display_hours' => 'decimal',
            'grouping' => 'users'
        ]);

        // Run through all user entries
        foreach ($report['data'] as $userEntry) {
            // Find the corresponding user
            $user = $userRepository->findOneBy(['togglId' => $userEntry['id']]);
            if (!($user instanceof User)) {
                echo sprintf('Skipping unknown user ID "%s"', $userEntry['id']).PHP_EOL;
                continue;
            }

            $total = $billable = $billableSum = 0;

            // Run through all time entries
            foreach ($userEntry['items'] as $userItem) {
                $total += intval($userItem['time'] / 1000);
                $billable += $userItem['sum'] ? intval($userItem['time'] / 1000) : 0;
                $billableSum += $userItem['sum'];
            }

            // Try to find an existing stats record
            $stats = $statsRepository->findOneBy(['user' => $user, 'date' => $dayStart]);
            if (!($stats instanceof Stats)) {
                $stats = new Stats();
                $stats->setUser($user);
                $stats->setDate($dayStart);
            }

            $stats->setTotal($total);
            $stats->setBillable($billable);
            $stats->setBillableSum($billableSum);

            try {
                $entityManager->persist($stats);
                $entityManager->flush();
                echo 'Updated stats record '.$dayStart->format('Y-m-d').' (workspace '.$workspace.') for user '.$user->getName().PHP_EOL;
            } catch (\PDOException $e) {
                echo 'Failed to update stats record '.$dayStart->format('Y-m-d').' (workspace '.$workspace.') for user '.$user->getName().PHP_EOL;
            }
        }

        // Next day
        $dayStart = $dayStart->modify('+1 day');
        $dayEnd = $dayEnd->modify('+1 day');
    }
}

$entityManager->flush();

require_once __DIR__.DIRECTORY_SEPARATOR.'update-overtimes.php';
