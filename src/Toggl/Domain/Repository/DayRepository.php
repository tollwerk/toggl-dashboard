<?php

/**
 * Toggl Dashboard
 *
 * @category    Apparat
 * @package     Tollwerk\Toggl
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
use Tollwerk\Toggl\Domain\Model\Day;
use Tollwerk\Toggl\Domain\Model\User;
use Tollwerk\Toggl\Ports\App;

/**
 * Day repository
 *
 * @package Tollwerk\Toggl
 * @subpackage Tollwerk\Toggl\Domain\Repository
 */
class DayRepository extends EntityRepository
{
    /**
     * Return all business holidays in a particular year
     *
     * @param int $year Year
     * @return array Business holidays
     */
    public function getBusinessHolidaysByYear($year)
    {
        $timezone = new \DateTimeZone(App::getConfig('common.timezone'));
        return $this->getBusinessHolidays(
            new \DateTimeImmutable($year.'-01-01', $timezone),
            new \DateTimeImmutable($year.'-12-31', $timezone)
        );
    }

    /**
     * Return all business holidays in a particular time range
     *
     * @param \DateTimeImmutable $from Start date
     * @param \DateTimeImmutable $to End date
     * @return array Business holidays
     */
    public function getBusinessHolidays(\DateTimeImmutable $from, \DateTimeImmutable $to)
    {
        try {
            $qb = App::getEntityManager()->createQueryBuilder();
            $qb->select('d')
                ->from('Tollwerk\Toggl\Domain\Model\Day', 'd')
                ->where('d.type = '.Day::BUSINESS_HOLIDAY)
                ->andWhere('d.date >= :from')
                ->andWhere('d.date <= :to')
                ->setParameter('from', $from->format('Y-m-d'))
                ->setParameter('to', $to->format('Y-m-d'))
                ->orderBy('d.date', 'ASC');
//            echo $qb->getQuery()->getSQL();
            return $qb->getQuery()->execute();
        } catch (QueryException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Return all personal holidays of a particular user in a particular year
     *
     * @param User $user User
     * @param int $year Year
     * @return array Business holidays
     */
    public function getPersonalHolidaysByYear(User $user, $year)
    {
        $timezone = new \DateTimeZone(App::getConfig('common.timezone'));
        return $this->getPersonalHolidays(
            $user,
            new \DateTimeImmutable($year.'-01-01', $timezone),
            new \DateTimeImmutable($year.'-12-31', $timezone)
        );
    }

    /**
     * Return all personal holidays of a particular user for a given period
     *
     * @param User $user User
     * @param \DateTimeImmutable $from Start date
     * @param \DateTimeImmutable $to End date
     * @return array Business holidays
     */
    public function getPersonalHolidays(User $user, \DateTimeImmutable $from, \DateTimeImmutable $to)
    {
        try {
            $qb = App::getEntityManager()->createQueryBuilder();
            $qb->select('d')
                ->from('Tollwerk\Toggl\Domain\Model\Day', 'd')
                ->where('d.type = '.Day::PERSONAL_HOLIDAY)
                ->andWhere('d.user = :user')
                ->andWhere('d.date >= :from')
                ->andWhere('d.date <= :to')
                ->setParameter('user', $user)
                ->setParameter('from', $from->format('Y-m-d'))
                ->setParameter('to', $to->format('Y-m-d'))
                ->orderBy('d.date', 'ASC');
//            echo $qb->getQuery()->getSQL();
            return $qb->getQuery()->execute();
        } catch (QueryException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Test if a particular day is a business or personal holiday for a particular user
     *
     * @param User $user User
     * @param \DateTimeInterface $date Date
     * @return bool Date is a holiday
     */
    public function isUserHoliday(User $user, \DateTimeInterface $date)
    {
        try {
            $qb = App::getEntityManager()->createQueryBuilder();
            $qb->select($qb->expr()->count('d.id'))
                ->from('Tollwerk\Toggl\Domain\Model\Day', 'd')
                ->where('d.date = :date')
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->eq('d.type', Day::BUSINESS_HOLIDAY),
                    $qb->expr()->andX(
                        $qb->expr()->eq('d.type', Day::PERSONAL_HOLIDAY),
                        $qb->expr()->eq('d.user', ':user')
                    )
                ))
                ->setParameter('user', $user)
                ->setParameter('date', $date->format('Y-m-d'));
            $result = $qb->getQuery()->execute();
            return boolval(current($result[0]));
        } catch (QueryException $e) {
            echo $e->getMessage();
        }
    }
}
