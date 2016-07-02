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

/**
 * Day
 *
 * @package Tollwerk\Toggl
 * @subpackage Tollwerk\Toggl\Domain\Model
 * @Entity(repositoryClass="Tollwerk\Toggl\Domain\Repository\ContractRepository")
 * @Table(name="contract",uniqueConstraints={@UniqueConstraint(name="userdate", columns={"user_id", "date"})})
 */
class Contract
{
    /**
     * Stats ID
     *
     * @var integer
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    /**
     * Date
     *
     * @var \DateTime
     * @Column(type="date")
     */
    protected $date;
    /**
     * Associated user
     *
     * @var User
     * @ManyToOne(targetEntity="Tollwerk\Toggl\Domain\Model\User", inversedBy="contracts")
     */
    protected $user;
    /**
     * Working days (bitmask; 0 = Sunday to 6 = Saturday)
     *
     * @var integer
     * @Column(type="integer")
     */
    protected $workingDays;
    /**
     * Working hours per day
     *
     * @var float
     * @Column(type="float")
     */
    protected $workingHoursPerDay;
    /**
     * Holidays per year
     *
     * @var integer
     * @Column(type="integer")
     */
    protected $holidaysPerYear;
    /**
     * Costs per month
     *
     * @var float
     * @Column(type="float")
     */
    protected $costsPerMonth;

    /**
     * Return the day ID
     *
     * @return int Day ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the day ID
     *
     * @param int $id Day ID
     * @return Day
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Return the date
     *
     * @return \DateTime Date
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the date
     *
     * @param \DateTime $date Date
     * @return Day
     */
    public function setDate(\DateTimeInterface $date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * Return the user
     *
     * @return User User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the user
     *
     * @param User $user User
     * @return Day
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Return the working days
     *
     * @return array Working days
     */
    public function getWorkingDays()
    {
        $workingDays = $this->workingDays;
        $workingDaysList = [];
        for ($workingDay = 0; $workingDay < 7; ++$workingDay) {
            if ($workingDays & 1) {
                $workingDaysList[] = $workingDay;
            }
            $workingDays >>= 1;
        }
        return $workingDaysList;
    }

    /**
     * Set the working days
     *
     * @param array $workingDaysList Working days
     */
    public function setWorkingDays(array $workingDaysList)
    {
        $workingDays = 0;
        foreach ($workingDaysList as $workingDay) {
            $workingDays |= pow(2, $workingDay);
        }
        $this->workingDays = $workingDays & 127;
    }

    /**
     * Return the working hours per day
     *
     * @return float Working hours per day
     */
    public function getWorkingHoursPerDay()
    {
        return $this->workingHoursPerDay;
    }

    /**
     * Set the working hours per day
     *
     * @param float $workingHoursPerDay Working hours per day
     */
    public function setWorkingHoursPerDay($workingHoursPerDay)
    {
        $this->workingHoursPerDay = $workingHoursPerDay;
    }

    /**
     * Return the personal holidays per year
     *
     * @return int Personal holidays per year
     */
    public function getHolidaysPerYear()
    {
        return $this->holidaysPerYear;
    }

    /**
     * Set the personal holidays per year
     *
     * @param int $holidaysPerYear Personal holidays per year
     */
    public function setHolidaysPerYear($holidaysPerYear)
    {
        $this->holidaysPerYear = $holidaysPerYear;
    }

    /**
     * Return the costs per month
     *
     * @return float Costs per month
     */
    public function getCostsPerMonth()
    {
        return $this->costsPerMonth;
    }

    /**
     * Set the costs per month
     *
     * @param float $costsPerMonth Costs per month
     */
    public function setCostsPerMonth($costsPerMonth)
    {
        $this->costsPerMonth = $costsPerMonth;
    }
}
