<?php
//===============================================
// Debug
//===============================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);


//===============================================
// Includes
//===============================================
require('config/KrisConfig.php');

require('framework/KrisController.php');
require('framework/KrisDB.php');
require('framework/KrisModel.php');
require('framework/KrisJoinedModel.php');
require('framework/KrisView.php');


//===============================================
// Database
//===============================================


//===============================================
// Autoloading for Business Classes
//===============================================
// Assumes Model Classes start with capital letters and Helpers start with lower case letters

function __autoload($className)
{
    if (KrisConfig::HasClass($className))
    {
        KrisConfig::Autoload($className);
    }   
    else
    {
        require_once(KrisConfig::$APP_PATH . 'models/' . $className . '.php');
    }
    
}


//===============================================
// Start the controller
//===============================================
$controller = new KrisController(KrisConfig::$APP_PATH . 'controllers/', KrisConfig::$WEB_FOLDER, 'main', 'index');