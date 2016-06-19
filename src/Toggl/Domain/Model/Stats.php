<?php

/**
 * Toggl Dashboard
 *
 * @category    Apparat
 * @package     Apparat\Server
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
 * @package Apparat\Server
 * @subpackage Tollwerk\Toggl\Domain\Model
 * @Entity(repositoryClass="Tollwerk\Toggl\Domain\Repository\StatsRepository")
 * @Table(name="stats",uniqueConstraints={@UniqueConstraint(name="userdate", columns={"user_id", "date"})})
 */
class Stats
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
     * @ManyToOne(targetEntity="Tollwerk\Toggl\Domain\Model\User", inversedBy="stats")
     */
    protected $user;
    /**
     * Total time
     *
     * @var integer
     * @Column(type="integer")
     */
    protected $total;
    /**
     * Billable time
     *
     * @var integer
     * @Column(type="integer")
     */
    protected $billable;
    /**
     * Billable sum
     *
     * @var float
     * @Column(type="float")
     */
    protected $billableSum;

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
     * Return the total time in seconds
     *
     * @return int Total time in seconds
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Return the billable time in seconds
     *
     * @return int Billable time in seconds
     */
    public function getBillable()
    {
        return $this->billable;
    }

    /**
     * Return the billable time in currency
     *
     * @return float Billable time in currency
     */
    public function getBillableSum()
    {
        return $this->billableSum;
    }

    /**
     * Set the total time in seconds
     *
     * @param int $total Total time in seconds
     * @return Stats Self reference
     */
    public function setTotal($total)
    {
        $this->total = $total;
        return $this;
    }

    /**
     * Set the billable time in seconds
     *
     * @param int $billable Billable time in seconds
     * @return Stats Self reference
     */
    public function setBillable($billable)
    {
        $this->billable = $billable;
        return $this;
    }

    /**
     * Set the billable time in currency
     *
     * @param float $billableSum Billable time in currency
     * @return Stats Self reference
     */
    public function setBillableSum($billableSum)
    {
        $this->billableSum = $billableSum;
        return $this;
    }
}
