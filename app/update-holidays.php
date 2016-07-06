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

use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Reader;
use Tollwerk\Toggl\Domain\Model\Day;
use Tollwerk\Toggl\Domain\Model\User;
use Tollwerk\Toggl\Ports\App;

require_once __DIR__.DIRECTORY_SEPARATOR.'bootstrap.php';

/**
 * Normalize a date to 00:00:00
 *
 * @param \DateTimeImmutable $date Date
 * @return \DateTimeImmutable Normalized date
 */
function normalizeDate(\DateTimeImmutable $date)
{
    $normalized = clone $date;
    return $normalized->setTime(0, 0, 0);
}

$entityManager = App::getEntityManager();
$userRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\User');
$dayRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\Day');
$timezone = new \DateTimeZone(App::getConfig('common.timezone'));
$today = new \DateTimeImmutable('today', $timezone);
$today = new \DateTimeImmutable('2016-01-01');

// Collect all users
$users = [];
/** @var User $user */
foreach ($userRepository->findAll() as $user) {
    $users[$user->getToken()] = $user;
    foreach ($user->getAliases() as $alias) {
        $users[$alias] =& $users[$user->getToken()];
    }
}

// Run through all calendars
$calendars = App::getConfig('calendar');
foreach ($calendars as $calendar) {
    // Skip if there's no valid calendar URL
    if (!empty($calendar['url'])) {
        $url = $calendar['url'];
        $excusedKeywords = (array_key_exists('excused', $calendar) && is_array($calendar['excused']))
            ? array_map('strtolower', array_filter($calendar['excused'])) : [];
        $refresh = empty($calendar['refresh']) ? 86400 : intval($calendar['refresh']);
        $type = empty($calendar['type']) ? Day::PERSONAL_HOLIDAY : intval($calendar['type']);

        $CALENDAR = fopen($url, 'r');
        $vcalendar = Reader::read($CALENDAR);

        // Run through all events
        /** @var VEvent $event */
        foreach ($vcalendar->VEVENT as $event) {
            $uuid = trim(strval($event->UID));
            if (empty($uuid)) {
                echo 'Skipping empty event ID'.PHP_EOL;
            }

            // Get the event description
            $description = trim($event->SUMMARY);

            // Skip if the event has past
            /** @var \DateTimeImmutable $startDate */
            $startDate = normalizeDate($event->DTSTART->getDateTime());
            $endDate = normalizeDate($event->DTEND->getDateTime()->modify('-1 second'));
            if ($endDate < $today) {
                continue;
            }

//            echo PHP_EOL.PHP_EOL.$event->DTSTART->serialize();
//            print_r($event->DTSTART->getDateTime());
//            print_r($startDate);

            // Validate and prepare the holiday data
            $user = null;
            $name = null;
            $overtime = false;
            $excused = false;

            // Type dependent processing
            switch ($type) {
                // Personal holiday
                case Day::PERSONAL_HOLIDAY:
                    // Get the summary / user name
                    $token = strtolower(strtok($description, ' '));

                    // Skip this event if the user name is empty
                    if (empty($token)) {
                        continue 2;
                    }

                    // Log and skip if the user is unknown
                    if (!array_key_exists($token, $users)) {
                        echo sprintf('Unknown user token "%s"', $token).PHP_EOL;
                        continue 2;
                    }

                    // Set the user
                    $user = $users[$token];

                    // Register the remainder
                    $name = trim(substr($description, strlen($token)));
                    if (!strlen($name)) {
                        $name = null;

                        // If a excused holiday keyword is given
                    } elseif (in_array(strtolower($name), $excusedKeywords)) {
                        $excused = true;

                        // Else: overtime reducing holiday
                    } else {
                        $overtime = true;
                    }
                    break;

                // Business holiday
                case Day::BUSINESS_HOLIDAY:
                    $name = $description;
                    break;
            }

            // Create or update all relevant days in the database
            // Run through the single days
            for ($date = clone $startDate; $date <= $endDate; $date = $date->modify('+1 day')) {
                // If this day has already past: skip
                if ($date < $today) {
                    continue;
                }

                // Try to find the holiday in the database
                $day = $dayRepository->findOneBy(['uuid' => $uuid, 'date' => $date, 'user' => $user]);

                // If the holiday doesn't exist yet: Create it
                if (!($day instanceof Day)) {
                    $day = new Day();
                }

                $day->setUuid($uuid);
                $day->setType($type);
                $day->setUser($user);
                $day->setName($name);
                $day->setDate($date);
                $day->setOvertime($overtime);
                $day->setExcused($excused);

                try {
                    $entityManager->persist($day);
                    $entityManager->flush();
                    if ($user instanceof User) {
                        echo 'Updated personal holiday '.$date->format('Y-m-d').' for user '.
                            ($user->getName() ?: '---').PHP_EOL;
                    } else {
                        echo 'Updated business holiday '.$date->format('Y-m-d').sprintf(' "%s"', $name).PHP_EOL;
                    }
                } catch (\PDOException $e) {
                    if ($user instanceof User) {
                        echo 'Failed to update personal holiday '.$date->format('Y-m-d').' for user '.
                            ($user->getName() ?: '---').'): Entry exists!'.PHP_EOL;
                    } else {
                        echo 'Failed to update business holiday '.$date->format('Y-m-d').
                            sprintf(' "%s"', $name).PHP_EOL;
                    }
                }
            }
        }
    }
}
