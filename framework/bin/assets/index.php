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

require(KrisConfig::FRAMEWORK_DIR.'/lib/controller/KrisController.php');
require(KrisConfig::FRAMEWORK_DIR.'/lib/view/KrisView.php');

// This will be loaded as needed...
KrisConfig::AddClass('KrisDB', KrisConfig::FRAMEWORK_DIR.'/lib/orm/KrisDB.php', true);
KrisConfig::AddClass('KrisModel', KrisConfig::FRAMEWORK_DIR.'/lib/orm/KrisModel.php', true);
KrisConfig::AddClass('KrisDBView', KrisConfig::FRAMEWORK_DIR.'/lib/orm/KrisDBView.php', true);
KrisConfig::AddClass('KrisCrudModel', KrisConfig::FRAMEWORK_DIR.'/lib/orm/KrisCrudModel.php', true);
KrisConfig::AddClass('HtmlHelpers', KrisConfig::FRAMEWORK_DIR.'/lib/helpers/HtmlHelpers.php', true);

KrisConfig::AddClass('ImageResizer', KrisConfig::FRAMEWORK_DIR.'/lib/helpers/ImageResizer.php', true);
KrisConfig::AddClass('NumberHelpers', KrisConfig::FRAMEWORK_DIR.'/lib/helpers/NumberHelpers.php', true);
/**
 * Autoloading for Business Classes
 *
 * @param $className
 * @return void
 */
function __autoload($className)
{
    if (KrisConfig::HasClass($className))
    {
        KrisConfig::Autoload($className);
    }   
    else
    {
        $path = '';
        if (strtolower(substr($className, -5)) == 'model')
        {
            $path = 'generated/';
        }
        else if (strtolower(substr($className, -4)) == 'view')
        {
            $path = 'crud/';
        }


        require(KrisConfig::APP_PATH . 'models/'. $path . $className . '.php');

    }
    
}


//===============================================
// Start the controller
//===============================================
$controller = new KrisController(dirname(__FILE__).'/'.KrisConfig::APP_PATH . 'controllers/', KrisConfig::WEB_FOLDER, 'main', 'index');
