<?php

/**
 * Toggl Dashboard
 *
 * @category    Apparat
 * @package     Tollwerk\Toggl
 * @subpackage  Tollwerk\Toggl\Domain\Model
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

namespace Tollwerk\Toggl\Domain\Model;

use Tollwerk\Toggl\Domain\Repository\ContractRepository;
use Tollwerk\Toggl\Domain\Repository\DayRepository;
use Tollwerk\Toggl\Domain\Repository\StatsRepository;
use Tollwerk\Toggl\Ports\App;

/**
 * User
 *
 * @package Tollwerk\Toggl
 * @subpackage Tollwerk\Toggl\Domain\Model
 * @Entity(repositoryClass="Tollwerk\Toggl\Domain\Repository\UserRepository")
 * @Table(name="user")
 */
class User
{
    /**
     * User ID
     *
     * @var integer
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    /**
     * Active
     *
     * @var boolean
     * @Column(type="boolean")
     */
    protected $active;
    /**
     * Toggl ID
     *
     * @var integer
     * @Column(type="integer", nullable=true)
     */
    protected $togglId;
    /**
     * User name
     *
     * @var string
     * @Column(length=64)
     */
    protected $name;
    /**
     * User token
     *
     * @var string
     * @Column(length=64)
     */
    protected $token;
    /**
     * User aliases
     *
     * @var string
     * @Column(length=256)
     */
    protected $aliases;
    /**
     * Overtime
     *
     * @var float
     * @Column(type="float")
     */
    protected $overtime;
    /**
     * List of all associated days
     *
     * @var Day[]
     * @OneToMany(targetEntity="Tollwerk\Toggl\Domain\Model\Day", mappedBy="user")
     */
    protected $days;
    /**
     * List of all associated stats
     *
     * @var Stats[]
     * @OneToMany(targetEntity="Tollwerk\Toggl\Domain\Model\Stats", mappedBy="user")
     */
    protected $stats;
    /**
     * List of all associated contracts
     *
     * @var Contract[]
     * @OneToMany(targetEntity="Tollwerk\Toggl\Domain\Model\Contract", mappedBy="user")
     */
    protected $contracts;

    /**
     * Return the user ID
     *
     * @return int User ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the user ID
     *
     * @param int $id User ID
     * @return User
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Return the Toggl ID
     *
     * @return int Toggl ID
     */
    public function getTogglId()
    {
        return $this->togglId;
    }

    /**
     * Set the Toggl ID
     *
     * @param int $togglId Toggl ID
     * @return User
     */
    public function setTogglId($togglId)
    {
        $this->togglId = $togglId;
        return $this;
    }

    /**
     * Return the user name
     *
     * @return string User name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the user name
     *
     * @param string $name User name
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Return the user token
     *
     * @return string User token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set the user token
     *
     * @param string $token User token
     * @return User
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Return the current overtime
     *
     * @return float Current overtime
     */
    public function getOvertime()
    {
        return $this->overtime;
    }

    /**
     * Set the current overtime
     *
     * @param float $overtime Current overtime
     * @return User
     */
    public function setOvertime($overtime)
    {
        $this->overtime = $overtime;
        return $this;
    }

    /**
     * Return the list of associated days
     *
     * @param int|null $year Optional: year
     * @return Day[] Days
     */
    public function getDays($year = null)
    {
        if ($year === null) {
            return $this->days;
        }

        $entityManager = App::getEntityManager();
        /** @var DayRepository $dayRepository */
        $dayRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\Day');
        $timezone = new \DateTimeZone(App::getConfig('common.timezone'));
        return $dayRepository->getPersonalHolidays(
            $this,
            new \DateTimeImmutable($year.'-01-01', $timezone),
            new \DateTimeImmutable($year.'-12-31', $timezone)
        );
    }

    /**
     * Return the list of associated statistics
     *
     * @return Stats[] Statistics
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * Return the list of effective contracts for a given period
     *
     * @param \DateTimeInterface|null $from Period start
     * @param \DateTimeInterface|null $to Period end
     * @return Contract[] Contracts
     */
    public function getContracts(\DateTimeInterface $from = null, \DateTimeInterface $to = null)
    {
        if (($from === null) && ($to === null)) {
            return $this->contracts;
        }

        $entityManager = App::getEntityManager();
        /** @var ContractRepository $contractRepository */
        $contractRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\Contract');
        return $contractRepository->getUserContracts($this, $from, $to);
    }

    /**
     * Return the effective contract for this user at a particular date
     *
     * @param \DateTimeInterface|null $date Date (default: now)
     * @return null|Contract Effective contract
     */
    public function getEffectiveContract(\DateTimeInterface $date = null)
    {
        if ($date === null) {
            $timezone = new \DateTimeZone(App::getConfig('common.timezone'));
            $date = new \DateTimeImmutable('now', $timezone);
        }
        $entityManager = App::getEntityManager();
        /** @var ContractRepository $contractRepository */
        $contractRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\Contract');
        return $contractRepository->getEffectiveUserContractForDate($this, $date);
    }

    /**
     * Return the user stats for a particular date (if any)
     *
     * @param \DateTimeInterface $date Date
     * @return null|Stats User stats for the date
     */
    public function getDateStats(\DateTimeInterface $date)
    {
        $entityManager = App::getEntityManager();
        /** @var StatsRepository $statsRepository */
        $statsRepository = $entityManager->getRepository('Tollwerk\Toggl\Domain\Model\Stats');
        return $statsRepository->getUserStatsByDate($this, $date);
    }

    /**
     * Set whether this is an active user
     *
     * @param boolean $active Active user
     * @return User
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * Return whether the user is ative
     *
     * @return boolean Active user
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Set the user aliases (comma-separated)
     *
     * @param string $aliases Comma separated aliases
     * @return User
     */
    public function setAliases($aliases)
    {
        $this->aliases = $aliases;
        return $this;
    }

    /**
     * Return the user aliases
     *
     * @return array User aliases
     */
    public function getAliases()
    {
        return array_filter(preg_split('%[^a-z]+%', $this->aliases));
    }
}
