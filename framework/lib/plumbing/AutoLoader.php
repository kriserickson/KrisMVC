<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Autoloader
 * @package plumbing
 */
class AutoLoader
{

    /**
     * @var array
     */
    private static $ClassLoader = array();

    /**
     * @var BucketContainer
     */
    public static $Container;

    /**
     * @static
     * @return BucketContainer
     */
    public static function Container()
    {
        return self::$Container;
    }

    /**
     *  Used by the autoloader to autoload classes...
     * @static
     * @param $className
     * @return bool
     */
    public static function HasClass($className)
    {
        return isset(self::$ClassLoader[$className]);
    }

    /**
     * Used by the autoloader to autoload classes...
     *
     * @static
     * @param $className
     * @return void
     */
    public static function Autoload($className)
    {
        /** @noinspection PhpIncludeInspection */
        require(self::$ClassLoader[$className]);
    }

    /**
     * Add a class to the AutoLoader.  For example if you wanted to add the DateHelpers class
     *
     * KrisConfig::AddClass('DateHelpers', 'app/library/DateHelpers.php');
     *
     * @static
     * @param string $className
     * @param string $classLocation
     * @param bool $isFramework
     * @return void
     */
    public static function AddClass($className, $classLocation, $isFramework = false)
    {
        if (!$isFramework)
        {
            $classLocation = KrisConfig::APP_PATH.$classLocation;
        }
        self::$ClassLoader[$className] = $classLocation;
    }





}

/**
 * Autoloading for Business Classes
 * @package plumbing
 * @param $className
 * @return void
 */
function __autoload($className)
{
    if (AutoLoader::HasClass($className))
    {
        AutoLoader::Autoload($className);
    }
    else
    {
        $path = '';
        if (strtolower(substr($className, -5)) == 'model')
        {
            $path = 'generated/';
        }
        else if (strtolower(substr($className, -4)) == 'view')
        {
            $path = 'crud/';
        }
        if (strlen($path) > 0 && !file_exists(KrisConfig::APP_PATH . 'models/'. $path . $className . '.php'))
        {
            $path = '';
        }


        $includeFile = KrisConfig::APP_PATH . 'models/' . $path . $className . '.php';
        if (file_exists($includeFile))
        {
            /** @noinspection PhpIncludeInspection */
            require($includeFile);
        }

    }

}

