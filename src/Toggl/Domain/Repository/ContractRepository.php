<?php

/**
 * Toggl Dashboard
 *
 * @category    Apparat
 * @package     Tollwerk\Toggl
 * @subpackage  Tollwerk\Toggl\Domain
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
use Tollwerk\Toggl\Domain\Model\Contract;
use Tollwerk\Toggl\Domain\Model\User;
use Tollwerk\Toggl\Ports\App;

/**
 * Contract repository
 *
 * @package Tollwerk\Toggl
 * @subpackage Tollwerk\Toggl\Domain
 */
class ContractRepository extends EntityRepository
{
    /**
     * Return the list of effective contracts of a particular user for a given period
     *
     * @param User $user User
     * @param \DateTimeInterface $from Start date
     * @param \DateTimeInterface $to End date
     * @return array Contracts ordered by timestamp
     */
    public function getUserContracts(User $user, \DateTimeInterface $from, \DateTimeInterface $to)
    {
        try {
            $qb = App::getEntityManager()->createQueryBuilder();
            $qb->select('c')
                ->from('Tollwerk\Toggl\Domain\Model\Contract', 'c')
                ->where('c.user = :user')
                ->setParameter('user', $user);

            // Set a lower boundary
            $fromContract = $this->getEffectiveUserContractForDate($user, $from);
            if ($fromContract instanceof Contract) {
                $qb->andWhere('c.date >= :from')
                    ->setParameter('from', $fromContract->getDate()->format('Y-m-d'));
            }

            // Set an upper boundary
            $toContract = $this->getEffectiveUserContractForDate($user, $to);
            if ($toContract instanceof Contract) {
                $qb->andWhere('c.date >= :to')
                    ->setParameter('to', $toContract->getDate()->format('Y-m-d'));
            }

            $qb->orderBy('c.date', 'ASC');
//            echo $qb->getQuery()->getSQL();
            return $qb->getQuery()->execute();
        } catch (QueryException $e) {
            echo $e->getMessage();
        }
        return [];
    }

    /**
     * Get the effective user contract for a particular date
     *
     * @param User $user User
     * @param \DateTimeInterface $date Date
     * @return Contract|null Effective user contract
     */
    public function getEffectiveUserContractForDate(User $user, \DateTimeInterface $date)
    {
        try {
            $qb = App::getEntityManager()->createQueryBuilder();
            $qb->select('c')
                ->from('Tollwerk\Toggl\Domain\Model\Contract', 'c')
                ->where('c.user = :user')
                ->andWhere('c.date <= :date')
                ->setParameter('user', $user)
                ->setParameter('date', $date->format('Y-m-d'))
                ->orderBy('c.date', 'DESC')
                ->setMaxResults(1);
//            echo $qb->getQuery()->getSQL();
            $results = $qb->getQuery()->execute();
            return count($results) ? current($results) : null;
        } catch (QueryException $e) {
            echo $e->getMessage();
        }
        return null;
    }
}
