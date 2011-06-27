<?php

class KrisConfig
{
    // Change Config options here... TODO: Load these from some config file
    const APP_PATH = 'app/';
    const WEB_FOLDER = '/krismvc';

    // Change Database connections here... TODO: Load these from some config file
    const DB_HOST = '';
    const DB_DATABASE = '';
    const DB_USER = '';
    const DB_PASSWORD = '';

    // Quote style
    public static $DATABASE_QUOTE_STYLE = KrisConfig::QuoteStyleMysql;

    private static $DB_CONNECTION;

    const DATE_STR = 'l jS \of F Y';

    const QuoteStyleMysql = 'MYSQL';
    const QuoteStyleMssql = 'MSSQL';

    private static $ClassLoader = array();


    /**
     * @static
     * @return PDO
     */
    public static function GetDatabaseHandler()
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

    public static function HasClass($className)
    {
        return isset(static::$ClassLoader[$className]);
    }

    public static function Autoload($className)
    {
        require(self::APP_PATH.static::$ClassLoader[$className]);
    }

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