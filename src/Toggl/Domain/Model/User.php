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
 * User
 *
 * @package Apparat\Server
 * @subpackage Tollwerk\Toggl\Domain\Model
 * @Entity
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
     * Toggl ID
     *
     * @var integer
     * @Column(type="integer")
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
     * List of all associated days
     *
     * @var Day[]
     * @OneToMany(targetEntity="Tollwerk\Toggl\Domain\Model\Day", mappedBy="user")
     */
    protected $days;

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
     * Return the list of associated days
     *
     * @return Day[] Days
     */
    public function getDays()
    {
        return $this->days;
    }
}
