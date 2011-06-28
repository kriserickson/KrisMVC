<?php

class KrisConfig
{
    // Change Config options here... TODO: Load these from some config file
    const APP_PATH = 'app/';
    const WEB_FOLDER = '/krismvc';

    // Change Database connections here... TODO: Load these from some config file
    const DB_HOST = 'localhost';
    const DB_DATABASE = 'ammara';
    const DB_USER = 'root';
    const DB_PASSWORD = 'myssirk34';

    // Quote style
    public static $DATABASE_QUOTE_STYLE = KrisConfig::QUOTE_STYLE_MYSQL;

    const DATE_STR = 'l jS \of F Y';

    /**
     * Database quote style enumeration.
     */
    const QUOTE_STYLE_MYSQL = 'MYSQL';
    const QUOTE_STYLE_MSSQL = 'MSSQL';
    const QUOTE_STYLE_ANSI = 'ANSI';



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
        if (is_null(static::$DB_CONNECTION))
        {
            try
            {
                static::$DB_CONNECTION = new PDO('mysql:host='.self::DB_HOST.';dbname='.self::DB_DATABASE, self::DB_USER, self::DB_PASSWORD);
            }
            catch (PDOException $e)
            {
                die('Connection failed: ' . $e->getMessage());
            }
        }
        return static::$DB_CONNECTION;
    }

    /**
     *  Used by the autoloader to autoload classes...
     * @static
     * @param $className
     * @return bool
     */
    public static function HasClass($className)
    {
        return isset(static::$ClassLoader[$className]);
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
        require(self::APP_PATH.static::$ClassLoader[$className]);
    }

    /**
     * Add a class to the AutoLoader.  For example if you wanted to add the DateHelpers class
     *
     * KrisConfig::AddClass('DateHelpers', 'app/library/DateHelpers.php');
     *
     * @static
     * @param $className
     * @param $classLocation
     * @return void
     */
    public static function AddClass($className, $classLocation)
    {
        static::$ClassLoader[$className] = $classLocation;
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