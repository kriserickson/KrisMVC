<?php

/**
 * Configuration of the application...
 */
class KrisConfig
{
    // Change Config options here...
    const APP_PATH = 'app/';    // APP_PATH must end in a slash...
    const WEB_FOLDER = '@@WEB_FOLDER@@';
    const BASE_DIR = '@@SITE_LOCATION@@';

    // Change Database connections here...
    const DB_HOST = '@@DB_HOST@@';
    const DB_DATABASE = '@@DB_DATABASE@@';
    const DB_USER = '@@DB_USER@@';
    const DB_PASSWORD = '@@DB_PASSWORD@@';

    const FRAMEWORK_DIR = '@@FRAMEWORK_DIR@@';

    // Quote style
    public static $DATABASE_TYPE = KrisConfig::DB_TYPE_MYSQL;

    const DATE_STR = 'l jS \of F Y';

    /**
     * Database quote style enumeration.
     */
    const DB_TYPE_MYSQL = 'MYSQL';

    // Currently only MySql has been tested...
    const DB_TYPE_MSSQL = 'MSSQL';
    const DB_TYPE_SQLITE = 'SQLITE';
    const DB_TYPE_POSTGRESQL = 'POSTGRESQL';




    /**
     * @var null|string|array
     */
    static $Error404Handler = null;
    

    /*  This can be a function, even one that calls another class...
    static $Error404Handler = array('ErrorClass', 'Display404');

    this gets called like:
    $d = new ErrorClass();
    $d->Display404($message);
     */


    /**
    * Error logging.
    *
    * @static
    * @param $message
    * @return void
    */
    public static function LogError($message)
    {
        // Default error logging action, change as necessary...
        error_log($message);
    }

    // End of user configuration...  Don't change the following unless you know what you are doing...

    /**
     * @var PDO
     */
    private static $DB_CONNECTION;

    /**
     * @var array
     */
    private static $ClassLoader = array();



    /**
     * Used by KrisDB and it's child classes to get a database connection.  Edit the DB_* static variables to configure
     *
     * @static
     * @return PDO
     */
    public static function GetDatabaseHandle()
    {
        if (is_null(KrisConfig::$DB_CONNECTION))
        {
            try
            {
                KrisConfig::$DB_CONNECTION = new PDO('mysql:host='.self::DB_HOST.';dbname='.self::DB_DATABASE, self::DB_USER, self::DB_PASSWORD);
            }
            catch (PDOException $e)
            {
                die('Connection failed: ' . $e->getMessage());
            }
        }
        return KrisConfig::$DB_CONNECTION;
    }

    /**
     *  Used by the autoloader to autoload classes...
     * @static
     * @param $className
     * @return bool
     */
    public static function HasClass($className)
    {
        return isset(KrisConfig::$ClassLoader[$className]);
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
        require(KrisConfig::$ClassLoader[$className]);
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
            $classLocation = self::APP_PATH.$classLocation;
        }
        KrisConfig::$ClassLoader[$className] = $classLocation;
    }




}

//===============================================
// Session
//===============================================
/*
session_start();
*/

//===============================================
// Uncaught Exception Handling
//===============================================s
/*
set_exception_handler('uncaught_exception_handler');

function uncaught_exception_handler($e) {
  ob_end_clean(); //dump out remaining buffered text
  $vars['message']=$e;
  die(View::do_fetch(KrisConfig::APP_PATH.'errors/exception_uncaught.php',$vars));
}

function custom_error($msg='') {
  $vars['msg']=$msg;
  die(View::do_fetch(KrisConfig::APP_PATH.'errors/custom_error.php',$vars));
}
*/

?>