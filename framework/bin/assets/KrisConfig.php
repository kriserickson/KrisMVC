<?php

/**
 * Configuration of the application...
 *
 * Change Config options here...
 */
class KrisConfig
{
    // Paths
    const APP_PATH = 'app/';    // APP_PATH must end in a slash...
    const WEB_FOLDER = '@@WEB_FOLDER@@';
    const BASE_DIR = '@@SITE_LOCATION@@';
	const FRAMEWORK_DIR = '@@FRAMEWORK_DIR@@';

    // Change Database connections here...
    const DB_HOST = '@@DB_HOST@@';
    const DB_DATABASE = '@@DB_DATABASE@@';
    const DB_USER = '@@DB_USER@@';
    const DB_PASSWORD = '@@DB_PASSWORD@@';

	// Debug
	const DEBUG = true;

    // Controllers and Actions
    const AUTH_CONTROLLER = 'auth';
    const DEFAULT_CONTROLLER = 'main';

    const DEFAULT_ACTION = 'index';


    // Options
    public static $AUTH_TYPE = KrisConfig::AUTH_TYPE_DB;     // Currently only DB is implimented...

    public static $DATABASE_TYPE = KrisConfig::DB_TYPE_MYSQL;

    /**
     * Database quote style enumeration.
     */
    const DB_TYPE_MYSQL = 'MYSQL';

    // Currently only MySql has been tested...
    const DB_TYPE_MSSQL = 'MSSQL';
    const DB_TYPE_SQLITE = 'SQLITE';
    const DB_TYPE_POSTGRESQL = 'POSTGRESQL';

    const AUTH_TYPE_DB = 'DB';
    const AUTH_TYPE_FILE = 'File';
    const AUTH_TYPE_LDAP = 'LDAP';
    const AUTH_TYPE_OPEN_AUTH = 'OpenAuth';


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


}



?>