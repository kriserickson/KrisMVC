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
     * @var KrisDIContainer
     */
    public static $Container;

    /**
     * @static
     * @return KrisDIContainer
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
        $require = self::$ClassLoader[$className];
        $fp = false;
        try
        {
            $fp = fopen($require, 'r', true);
        } catch (Exception $ex) {}
        if ($fp)
        {
            fclose($fp);
            /** @noinspection PhpIncludeInspection */
            require($require);
        }
        else
        {
            throw new Exception('AutoLoaded class: '.$className.' has an invalid include path: '. $require);
        }
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
        if ($isFramework)
        {
            $classLocation = KrisConfig::FRAMEWORK_DIR.$classLocation;
        }
        else
        {
            $classLocation = KrisConfig::APP_PATH.$classLocation;
        }

        self::$ClassLoader[$className] = $classLocation;
    }

    /**
     * Add a bunch of classes of the same type to the Autoloader...
     *
     * @static
     * @param $classes
     * @param bool $isFramework
     */
    public static function AddClasses($classes, $isFramework = false)
    {
        foreach ($classes as $className => $classLocation)
        {
            self::AddClass($className, $classLocation, $isFramework);
        }
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
        $includeFile = KrisConfig::BASE_DIR.'/'. KrisConfig::APP_PATH. 'models/' . $path . $className . '.php';
        if (file_exists($includeFile))
        {
            /** @noinspection PhpIncludeInspection */
            require($includeFile);
        }

    }

}

