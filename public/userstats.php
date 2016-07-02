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

use Tollwerk\Toggl\Application\Service\StatisticsService;
use Tollwerk\Toggl\Domain\Model\User;
use Tollwerk\Toggl\Infrastructure\Renderer\Html;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'bootstrap.php';

$entityManager = \Tollwerk\Toggl\Ports\App::getEntityManager();
$userRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\User');

if (empty($_GET['user'])):
    ?><!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="refresh" content="900; URL=index.php">
        <title>Toggl Dashboard</title>
        <link href="/css/dashboard.css" type="text/css" rel="stylesheet"/>
    </head>
    <body>
        <h1>Please pick a user</h1>
        <ul><?php
            /** @var User $user */
            foreach ($userRepository->findAll() as $user):
                ?><li><a href="userstats.php?user=<?= $user->getId(); ?>"><?= Html::h($user->getName()).' ('.$user->getId().')'; ?></a></li><?php
            endforeach;
        ?></ul>
    </body>
</html><?php
else:
    header('Content-Type: application/json');
    $user = $userRepository->find(intval($_GET['user']));
    die(json_encode(StatisticsService::getUserStatistics($user)));
endif;
