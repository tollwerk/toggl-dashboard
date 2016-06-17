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

use Tollwerk\Toggl\Ports\App;
use Tollwerk\Toggl\Domain\Model\User;

require_once __DIR__.DIRECTORY_SEPARATOR.'bootstrap.php';

$entityManager = App::getEntityManager();
$userRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\User');

$togglClient = App::getTogglClient();
$workspaces = App::getConfig('toggl.workspaces');

// Run through all workspaces
foreach ($workspaces as $workspace) {
    $workspaceUsers = $togglClient->getWorkspaceUsers(['id' => $workspace]);

    // Run through all workspace users
    foreach ($workspaceUsers as $workspaceUser) {

        // Try to find the user in the database
        $user = $userRepository->findOneBy(['togglId' => $workspaceUser['id']]);

        // If the user doesn't already exists
        if (!($user instanceof User)) {
            $user = new User();
            $user->setTogglId($workspaceUser['id']);
        }

        $user->setName($workspaceUser['fullname']);
        $user->setToken(strtolower($workspaceUser['fullname']));

        echo 'Updated user '.$user->getName().PHP_EOL;

        $entityManager->persist($user);
    }
}

$entityManager->flush();
