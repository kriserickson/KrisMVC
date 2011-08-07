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
require KrisConfig::FRAMEWORK_DIR.'/lib/controller/KrisController.php';
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

// Authentication
AutoLoader::AddClass('Auth', KrisConfig::FRAMEWORK_DIR.'/lib/auth/Auth.php', true);
AutoLoader::AddClass('Auth_DB', KrisConfig::FRAMEWORK_DIR.'/lib/auth/Auth_DB.php', true);
AutoLoader::AddClass('PasswordCheck', KrisConfig::FRAMEWORK_DIR.'/lib/auth/PasswordCheck.php', true);
AutoLoader::AddClass('PasswordHash', KrisConfig::FRAMEWORK_DIR.'/lib/auth/PasswordHash.php', true);
AutoLoader::AddClass('Session', KrisConfig::FRAMEWORK_DIR.'/lib/auth/Session.php', true);
AutoLoader::AddClass('User', KrisConfig::FRAMEWORK_DIR.'/lib/auth/User.php', true);

// Helpers
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
    AutoLoader::AddClass('DebugController', KrisConfig::FRAMEWORK_DIR.'/lib/debug/DebugController.php', true);
    AutoLoader::AddClass('DebugPDO', KrisConfig::FRAMEWORK_DIR.'/lib/debug/DebugPDO.php', true);
    AutoLoader::AddClass('DebugLog', KrisConfig::FRAMEWORK_DIR.'/lib/debug/DebugLog.php', true);


    $classes = array('Controller' => 'DebugController', 'Log' => 'DebugLog');
    $databaseClass = 'DebugPDO';
}
else
{
    ini_set('display_errors', 'On');
    error_reporting(E_ERROR);
    $classes = array('Controller' => 'KrisController', 'Log' => 'KrisLog');
    $databaseClass = 'PDO';
}

// Eventually this will improve with 5.3 and better lambda functions and closures...
$factory = array('PDO' => create_function('$container', '$dsn = "mysql:host=".KrisConfig::DB_HOST.";dbname=".KrisConfig::DB_DATABASE;'.PHP_EOL.
    'return new '.$databaseClass.'($dsn, KrisConfig::DB_USER, KrisConfig::DB_PASSWORD);'),
    'PasswordCheck' => create_function('', 'return new PasswordHash(8,true);'));

AutoLoader::$Container = new BucketContainer($factory);
foreach ($classes as $interface => $useClass)
{
    AutoLoader::$Container->registerImplementation($interface, $useClass);
}



?>