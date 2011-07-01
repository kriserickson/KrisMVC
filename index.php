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

require('framework/lib/KrisController.php');
require('framework/lib/KrisDB.php');
require('framework/lib/KrisModel.php');
require('framework/lib/KrisDBView.php');
require('framework/lib/KrisView.php');


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
        if (substr($className, -5) == 'Model')
        {
            require(KrisConfig::APP_PATH . 'models/Generated/' . $className . '.php');
        }
        else
        {
            require(KrisConfig::APP_PATH . 'models/' . $className . '.php');
        }
    }
    
}


//===============================================
// Start the controller
//===============================================
$controller = new KrisController(KrisConfig::APP_PATH . 'controllers/', KrisConfig::WEB_FOLDER, 'main', 'index');
