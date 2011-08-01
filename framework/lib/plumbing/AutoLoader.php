<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
 *
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

        /** @noinspection PhpIncludeInspection */
        require(KrisConfig::APP_PATH . 'models/'. $path . $className . '.php');

    }

}

