<?php

/**
 * Toggl Dashboard
 *
 * @category    Apparat
 * @package     Apparat\Server
 * @subpackage  Tollwerk\Toggl\Domain\Repository
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

namespace Tollwerk\Toggl\Domain\Repository;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\QueryException;
use Tollwerk\Toggl\Domain\Model\Stats;
use Tollwerk\Toggl\Domain\Model\User;
use Tollwerk\Toggl\Ports\App;

/**
 * Stats repository
 *
 * @package Apparat\Server
 * @subpackage Tollwerk\Toggl\Domain\Repository
 */
class StatsRepository extends EntityRepository
{
    /**
     * Return all stats of a particular user in a particular year
     *
     * @param User $user User
     * @param int $year Year
     * @return array User stats
     */
    public function getUserStatsByYear(User $user, $year)
    {
        try {
            $qb = App::getEntityManager()->createQueryBuilder();
            $qb->select('s')
                ->from('Tollwerk\Toggl\Domain\Model\Stats', 's')
                ->where('s.user = :user')
                ->andWhere('YEAR(s.date) = '.intval($year))
                ->setParameter('user', $user);
//            echo $qb->getQuery()->getSQL();
            return $qb->getQuery()->execute();
        } catch (QueryException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Return all stats of a user starting at a particular offset date
     *
     * @param User $user User
     * @param \DateTimeInterface $offsetDate Offset date
     * @return array User stats
     */
    public function getUserStatsByOffset(User $user, \DateTimeInterface $offsetDate)
    {
        try {
            $qb = App::getEntityManager()->createQueryBuilder();
            $qb->select('s')
                ->from('Tollwerk\Toggl\Domain\Model\Stats', 's')
                ->where('s.user = :user')
                ->andWhere('s.date >= '.$offsetDate->format('Y-m-d'))
                ->setParameter('user', $user);
//            echo $qb->getQuery()->getSQL();
            return $qb->getQuery()->execute();
        } catch (QueryException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Return the stats of a user at a particular date (if any)
     *
     * @param User $user User
     * @param \DateTimeInterface Date
     * @return Stats|null User stats
     */
    public function getUserStatsByDate(User $user, \DateTimeInterface $date)
    {
        try {
            $qb = App::getEntityManager()->createQueryBuilder();
            $qb->select('s')
                ->from('Tollwerk\Toggl\Domain\Model\Stats', 's')
                ->where('s.user = :user')
                ->andWhere('s.date = :date')
                ->setParameter('user', $user)
                ->setParameter('date', $date->format('Y-m-d'));
//            echo $qb->getQuery()->getSQL();
            $result = $qb->getQuery()->execute();
            return count($result) ? current($result) : null;
        } catch (QueryException $e) {
            echo $e->getMessage();
        }
    }
}
