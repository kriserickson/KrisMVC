<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

// These are the bare bones files required to run the application...
define('KRIS_MVC_VERSION', "0.8");

require KrisConfig::FRAMEWORK_DIR.'/lib/controller/KrisRouter.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/controller/DefaultController.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/controller/RouteRequest.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/controller/Request.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/plumbing/KrisDIContainer.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/plumbing/AutoLoader.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/view/KrisView.php';


// This will be loaded as needed...
AutoLoader::AddClasses(array(
    // Database
    'KrisDB' => '/lib/orm/KrisDB.php', 'KrisModel' =>'/lib/orm/KrisModel.php',
    'KrisDBView' => '/lib/orm/KrisDBView.php', 'KrisCrudModel' => '/lib/orm/KrisCrudModel.php',
    // Log
    'Log' => '/lib/log/Log.php', 'KrisLog' => '/lib/log/Log.php',
    // Cache
    'DbCache' => '/lib/cache/DbCache.php', 'FileCache' => '/lib/cache/FileCache.php', 'Cache' => '/lib/cache/Cache.php', 'ApcCache' => '/lib/cache/ApcCache.php',
// Authentication
    'Auth' => '/lib/auth/Auth.php', 'Auth_DB' => '/lib/auth/Auth_DB.php', 'PasswordCheck' => '/lib/auth/PasswordCheck.php',
    'PasswordHash' => '/lib/auth/PasswordHash.php', 'Session' => '/lib/auth/Session.php', 'User' => '/lib/auth/User.php',
// Helpers
    'FileHelpers' => '/lib/helpers/FileHelpers.php', 'HtmlHelpers' => '/lib/helpers/HtmlHelpers.php', 'ImageResizer' => '/lib/helpers/ImageResizer.php',
    'NumberHelpers' => '/lib/helpers/NumberHelpers.php',
    // Cron Stuff
    'CronModel' => '/lib/plumbing/CronModel.php', 'CronLogModel' => '/lib/plumbing/CronLogModel.php', 'KrisCronManager' => '/lib/plumbing/KrisCronManager.php', 'CronBase' => '/lib/plumbing/CronBase.php',
// Alternate Views
    'MustacheView' => '/lib/view/MustacheView.php'), true);


//===============================================
// Debug
//===============================================
if (KrisConfig::DEBUG)
{
    // Setup debug
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
    AutoLoader::AddClasses(array('DebugRouter' => '/lib/debug/DebugRouter.php',   'DebugPDO' => '/lib/debug/DebugPDO.php', 'DebugLog' => '/lib/debug/DebugLog.php'), true);

    $errorHandler = E_ALL | E_STRICT;

    $classes = array('Router' => 'DebugRouter', 'Log' => 'DebugLog');
    $databaseClass = 'DebugPDO';
}
else
{
    ini_set('display_errors', 'On');

    $errorHandler = E_ERROR;

    $classes = array('Router' => 'KrisRouter', 'Log' => 'KrisLog');
    $databaseClass = 'PDO';
}

error_reporting($errorHandler);

$factory = array('PDO' => function() use($databaseClass) {
        $dsn = "mysql:host=".KrisConfig::DB_HOST.";dbname=".KrisConfig::DB_DATABASE;
        return new $databaseClass($dsn, KrisConfig::DB_USER, KrisConfig::DB_PASSWORD);
    },
    'PasswordCheck' => function() {
        return new PasswordHash(8,true);
    },
    'Cache' => function()
    {
        // This could be register implementation however Cache's may need to be configured...
        if (KrisConfig::$CACHE_TYPE == KrisConfig::CACHE_TYPE_APC)
        {
        return new ApcCache();
        }
        else if (KrisConfig::$CACHE_TYPE == KrisConfig::CACHE_TYPE_DB)
        {
            return new DBCache();
        }
        else if (KrisConfig::$CACHE_TYPE == KrisConfig::CACHE_TYPE_FILE)
        {
            return new FileCache();
        }
        else
        {
            throw new Exception('Unsupported cache type: '.KrisConfig::$CACHE_TYPE);
        }
    });

AutoLoader::$Container = new KrisDIContainer($factory);
foreach ($classes as $interface => $useClass)
{
    AutoLoader::Container()->registerImplementation($interface, $useClass);
}

// Turn error handlers into exceptions...
/**
 * @param int $errorNumber
 * @param string $errorString
 * @param string $errorFile
 * @param int $errorLine
 * @throws ErrorException
 */
function exception_error_handler($errorNumber, $errorString, $errorFile, $errorLine )
{
    if ($errorNumber != E_USER_NOTICE)
    {
        throw new ErrorException($errorString, 0, $errorNumber, $errorFile, $errorLine);
    }
    else
    {
        /** @var $log DebugLog */
        $log = AutoLoader::Container()->get('Log');
        $log->Debug('File: '.$errorFile.' Line: '.$errorLine.' '.$errorString);
    }

}
set_error_handler('exception_error_handler', $errorHandler );

