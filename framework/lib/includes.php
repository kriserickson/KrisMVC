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

require KrisConfig::FRAMEWORK_DIR.'/lib/controller/KrisController.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/controller/DefaultController.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/controller/RouteRequest.php';
require KrisConfig::FRAMEWORK_DIR.'/lib/view/KrisView.php';

// This will be loaded as needed...
KrisConfig::AddClass('KrisDB', KrisConfig::FRAMEWORK_DIR.'/lib/orm/KrisDB.php', true);
KrisConfig::AddClass('KrisModel', KrisConfig::FRAMEWORK_DIR.'/lib/orm/KrisModel.php', true);
KrisConfig::AddClass('KrisDBView', KrisConfig::FRAMEWORK_DIR.'/lib/orm/KrisDBView.php', true);
KrisConfig::AddClass('KrisCrudModel', KrisConfig::FRAMEWORK_DIR.'/lib/orm/KrisCrudModel.php', true);

// Authentication
KrisConfig::AddClass('Auth', KrisConfig::FRAMEWORK_DIR.'/lib/auth/Auth.php', true);
KrisConfig::AddClass('Auth_DB', KrisConfig::FRAMEWORK_DIR.'/lib/auth/Auth_DB.php', true);
KrisConfig::AddClass('PasswordHash', KrisConfig::FRAMEWORK_DIR.'/lib/auth/PasswordHash.php', true);
KrisConfig::AddClass('Session', KrisConfig::FRAMEWORK_DIR.'/lib/auth/Session.php', true);
KrisConfig::AddClass('User', KrisConfig::FRAMEWORK_DIR.'/lib/auth/User.php', true);

// Helpers
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


?>