<?php

/**
 * Toggl Dashboard
 *
 * @category    Apparat
 * @package     Apparat\Server
 * @subpackage  Tollwerk\Toggl\Ports
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

namespace Tollwerk\Toggl\Ports;

use AJT\Toggl\TogglClient;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Symfony\Component\Yaml\Yaml;

/**
 * App
 *
 * @package Apparat\Server
 * @subpackage Tollwerk\Toggl\Ports
 */
class App
{
    /**
     * Configuration
     *
     * @var array
     */
    protected static $config;
    /**
     * Root directory
     *
     * @var string
     */
    protected static $rootDirectory;
    /**
     * Entity manager
     *
     * @var EntityManager
     */
    protected static $entityManager;
    /**
     * Developer mode
     *
     * @var boolean
     */
    protected static $devMode;
    /**
     * Toggl client
     *
     * @var TogglClient
     */
    protected static $togglClient;

    /**
     * Bootstrap
     *
     * @see https://github.com/toggl/toggl_api_docs/blob/master/reports.md
     * @see http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/configuration.html
     *
     * @param bool $devMode Developer mode
     */
    public static function bootstrap($devMode = false)
    {
        self::$devMode = !!$devMode;
        self::$rootDirectory = dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR;

        $config = file_get_contents(self::$rootDirectory.'config'.DIRECTORY_SEPARATOR.'config.yml');
        self::$config = Yaml::parse($config);
//        print_r(self::$config);

        self::initializeDoctrine();
    }

    /**
     * Initialize Doctrine
     */
    protected static function initializeDoctrine()
    {
        // If the Doctrine parameters don't exist
        if (empty(self::$config['doctrine'])
            || !is_array(self::$config['doctrine'])
            || empty(self::$config['doctrine']['dbparams'])
        ) {
            throw new \InvalidArgumentException('Invalid Doctrine database parameters', 1466175889);
        }

        $modelPaths = [
            self::$rootDirectory.'src'.DIRECTORY_SEPARATOR.'Toggl'
            .DIRECTORY_SEPARATOR.'Domain'.DIRECTORY_SEPARATOR.'Model'
        ];
        $dbParams = self::$config['doctrine']['dbparams'];
        $config = Setup::createAnnotationMetadataConfiguration($modelPaths, self::$devMode);
        self::$entityManager = EntityManager::create($dbParams, $config);
    }

    /**
     * Return the entity manager
     *
     * @return EntityManager
     */
    public static function getEntityManager()
    {
        return self::$entityManager;
    }

    /**
     * Return a Toggl client
     *
     * @return TogglClient Toggl client
     */
    public static function getTogglClient()
    {
        if (self::$togglClient === null) {
            self::$togglClient = TogglClient::factory([
                'api_key' => self::$config['toggl']['apiKey'],
                'debug' => false
            ]);
        }
        return self::$togglClient;
    }

    /**
     * Get a configuration value
     *
     * @param null $key Optional: config value key
     * @return mixed Configuration value(s)
     */
    public static function getConfig($key = null)
    {
        if ($key === null) {
            return self::$config;
        }
        $keyParts = explode('.', $key);
        $config =& self::$config;
        foreach ($keyParts as $keyPart) {
            if (!array_key_exists($keyPart, $config)) {
                throw new \InvalidArgumentException(sprintf('Invalid config key "%s"', $key), 1466179561);
            }
            $config =& $config[$keyPart];
        }
        return $config;
    }
}
