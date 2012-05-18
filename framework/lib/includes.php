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

/** @noinspection PhpIncludeInspection */
require KrisConfig::FRAMEWORK_DIR.'/lib/controller/KrisRouter.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/controller/DefaultController.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/controller/RouteRequest.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/controller/Request.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/plumbing/BucketContainer.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/plumbing/AutoLoader.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/view/KrisView.php';


// This will be loaded as needed...
AutoLoader::AddClass('KrisDB', KrisConfig::FRAMEWORK_DIR.'/lib/orm/KrisDB.php', true);
AutoLoader::AddClass('KrisModel', KrisConfig::FRAMEWORK_DIR.'/lib/orm/KrisModel.php', true);
AutoLoader::AddClass('KrisDBView', KrisConfig::FRAMEWORK_DIR.'/lib/orm/KrisDBView.php', true);
AutoLoader::AddClass('KrisCrudModel', KrisConfig::FRAMEWORK_DIR.'/lib/orm/KrisCrudModel.php', true);
AutoLoader::AddClass('KrisLog', KrisConfig::FRAMEWORK_DIR.'/lib/log/Log.php', true);

AutoLoader::AddClass('Cache', KrisConfig::FRAMEWORK_DIR.'/lib/cache/Cache.php', true);
AutoLoader::AddClass('ApcCache', KrisConfig::FRAMEWORK_DIR.'/lib/cache/ApcCache.php', true);
AutoLoader::AddClass('DbCache', KrisConfig::FRAMEWORK_DIR.'/lib/cache/DbCache.php', true);
AutoLoader::AddClass('FileCache', KrisConfig::FRAMEWORK_DIR.'/lib/cache/FileCache.php', true);

// Authentication
AutoLoader::AddClass('Auth', KrisConfig::FRAMEWORK_DIR.'/lib/auth/Auth.php', true);
AutoLoader::AddClass('Auth_DB', KrisConfig::FRAMEWORK_DIR.'/lib/auth/Auth_DB.php', true);
AutoLoader::AddClass('PasswordCheck', KrisConfig::FRAMEWORK_DIR.'/lib/auth/PasswordCheck.php', true);
AutoLoader::AddClass('PasswordHash', KrisConfig::FRAMEWORK_DIR.'/lib/auth/PasswordHash.php', true);
AutoLoader::AddClass('Session', KrisConfig::FRAMEWORK_DIR.'/lib/auth/Session.php', true);
AutoLoader::AddClass('User', KrisConfig::FRAMEWORK_DIR.'/lib/auth/User.php', true);

// Helpers
AutoLoader::AddClass('FileHelpers', KrisConfig::FRAMEWORK_DIR.'/lib/helpers/FileHelpers.php', true);
AutoLoader::AddClass('HtmlHelpers', KrisConfig::FRAMEWORK_DIR.'/lib/helpers/HtmlHelpers.php', true);
AutoLoader::AddClass('ImageResizer', KrisConfig::FRAMEWORK_DIR.'/lib/helpers/ImageResizer.php', true);
AutoLoader::AddClass('NumberHelpers', KrisConfig::FRAMEWORK_DIR.'/lib/helpers/NumberHelpers.php', true);

// Alternate Views
AutoLoader::AddClass('MustacheView', KrisConfig::FRAMEWORK_DIR.'/lib/view/MustacheView.php', true);
AutoLoader::AddClass('Log', KrisConfig::FRAMEWORK_DIR.'/lib/log/Log.php', true);


//===============================================
// Debug
//===============================================
if (KrisConfig::DEBUG)
{
    // Setup debug
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
    AutoLoader::AddClass('DebugRouter', KrisConfig::FRAMEWORK_DIR.'/lib/debug/DebugRouter.php', true);
    AutoLoader::AddClass('DebugPDO', KrisConfig::FRAMEWORK_DIR.'/lib/debug/DebugPDO.php', true);
    AutoLoader::AddClass('DebugLog', KrisConfig::FRAMEWORK_DIR.'/lib/debug/DebugLog.php', true);

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
        return new ApcCache();
    });

AutoLoader::$Container = new BucketContainer($factory);
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


?>