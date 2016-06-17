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

use \Tollwerk\Toggl\Ports\App;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Reader;
use Tollwerk\Toggl\Domain\Model\Day;
use Tollwerk\Toggl\Domain\Model\User;

require_once __DIR__.DIRECTORY_SEPARATOR.'bootstrap.php';

$entityManager = App::getEntityManager();
$userRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\User');
$dayRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\Day');
$now = new \DateTimeImmutable('now');

// Collect all users
$users = [];
/** @var User $user */
foreach ($userRepository->findAll() as $user) {
    $users[$user->getToken()] = $user;
}

// Register name aliases
$aliases = App::getConfig('user.alias');
foreach ($users as $token => $user) {
    if (array_key_exists($token, $aliases) && is_array($aliases[$token])) {
        foreach ($aliases[$token] as $alias) {
            $users[$alias] =& $users[$token];
        }
    }
}

// Run through all calendars
$calendars = App::getConfig('calendar');
foreach ($calendars as $calendar) {
    // Skip if there's no valid calendar URL
    if (!empty($calendar['url'])) {
        $url = $calendar['url'];
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
            /** @var \DateTimeImmutable $date */
            $date = $event->DTSTART->getDateTime();
            if ($date < $now) {
                continue;
            }

            // Try to find the event in the database
            $day = $dayRepository->findOneBy(['uuid' => $uuid]);
            $user = null;
            $name = null;

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
                    break;

                // Business holiday
                case Day::BUSINESS_HOLIDAY:
                    $name = $description;
                    break;
            }

            // If the day doesn't exist yet: Create it
            if (!($day instanceof Day)) {
                $day = new Day();
                $day->setUuid($uuid);
                $day->setType($type);
            }

            $day->setUser($user);
            $day->setName($name);
            $day->setDate($date);

            echo 'Updated day '.$date->format('c').' ('.$day->getUuid().')'.PHP_EOL;

            $entityManager->persist($day);
        }
    }
}

$entityManager->flush();
