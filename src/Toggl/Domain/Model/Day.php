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
 * @Entity
 * @Table(name="day",uniqueConstraints={@UniqueConstraint(name="userdate", columns={"user_id", "date", "uuid"})})
 */
class Day
{
    /**
     * Day ID
     *
     * @var integer
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    /**
     * Day UUID
     *
     * @var string
     * @Column(type="string", length=64)
     */
    protected $uuid;
    /**
     * Day name
     *
     * @var string
     * @Column(type="string", nullable=true, length=64)
     */
    protected $name;
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
     * @ManyToOne(targetEntity="Tollwerk\Toggl\Domain\Model\User", inversedBy="days")
     */
    protected $user;
    /**
     * Day type
     *
     * @var int
     * @Column(type="integer");
     */
    protected $type;
    /**
     * Personal holiday
     *
     * @var int
     */
    const PERSONAL_HOLIDAY = 0;
    /**
     * Business holiday
     *
     * @var int
     */
    const BUSINESS_HOLIDAY = 1;

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
     * Set the holiday type
     *
     * @return int Holiday type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Return the holiday type
     *
     * @param int $type Holiday type
     * @return Day
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get the unique event UUID
     *
     * @return string Unique event UUID
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set the unique event UUID
     *
     * @param string $uuid Unique event UUID
     * @return Day
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * Return the name
     *
     * @return string Name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name
     *
     * @param string $name Name
     * @return Day
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}
