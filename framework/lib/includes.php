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

// Authentication
AutoLoader::AddClass('Auth', KrisConfig::FRAMEWORK_DIR.'/lib/auth/Auth.php', true);
AutoLoader::AddClass('Auth_DB', KrisConfig::FRAMEWORK_DIR.'/lib/auth/Auth_DB.php', true);
AutoLoader::AddClass('PasswordHash', KrisConfig::FRAMEWORK_DIR.'/lib/auth/PasswordHash.php', true);
AutoLoader::AddClass('Session', KrisConfig::FRAMEWORK_DIR.'/lib/auth/Session.php', true);
AutoLoader::AddClass('User', KrisConfig::FRAMEWORK_DIR.'/lib/auth/User.php', true);

// Helpers
AutoLoader::AddClass('HtmlHelpers', KrisConfig::FRAMEWORK_DIR.'/lib/helpers/HtmlHelpers.php', true);
AutoLoader::AddClass('ImageResizer', KrisConfig::FRAMEWORK_DIR.'/lib/helpers/ImageResizer.php', true);
AutoLoader::AddClass('NumberHelpers', KrisConfig::FRAMEWORK_DIR.'/lib/helpers/NumberHelpers.php', true);

AutoLoader::$Container = new bucket_Container();

//===============================================
// Debug
//===============================================
if (KrisConfig::DEBUG)
{
    // Setup debug
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
    AutoLoader::AddClass('DebugController', KrisConfig::FRAMEWORK_DIR.'/lib/controller/DebugController.php', true);
    AutoLoader::AddClass('DebugPDO', KrisConfig::FRAMEWORK_DIR.'/lib/orm/DebugPDO.php', true);
    AutoLoader::$Container->registerImplementation('Controller', 'DebugController');

}
else
{
    ini_set('display_errors', 'Off');
    error_reporting(E_ERROR);
    AutoLoader::$Container->registerImplementation('Controller', 'KrisController');
}




?>