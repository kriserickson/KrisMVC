<?php

if (!defined('__DIR__'))
{
    define('__DIR__', dirname(__FILE__));
}

require __DIR__ . '/../../config/KrisConfig.php';
require __DIR__ . '/../../framework/lib/auth/PasswordCheck.php';
require __DIR__ . '/../../framework/lib/auth/PasswordHash.php';
require __DIR__ . '/../../framework/lib/cache/Cache.php';
require __DIR__ . '/../../framework/lib/controller/KrisRouter.php';
require __DIR__ . '/../../framework/lib/controller/RouteRequest.php';
require __DIR__ . '/../../framework/lib/controller/Request.php';
require __DIR__ . '/../../framework/lib/log/Log.php';
require __DIR__ . '/../../framework/lib/orm/KrisDB.php';
require __DIR__ . '/../../framework/lib/orm/KrisModel.php';
require __DIR__ . '/../../framework/lib/orm/KrisCrudModel.php';
require __DIR__ . '/../../framework/lib/orm/KrisDBView.php';
require __DIR__ . '/../../framework/lib/plumbing/BucketContainer.php';
require __DIR__ . '/../../framework/lib/view/KrisView.php';
require __DIR__ . '/../../framework/lib/view/Mustache.php';
require __DIR__ . '/../../framework/lib/helpers/HtmlHelpers.php';
require __DIR__ . '/../../framework/lib/helpers/NumberHelpers.php';



require 'Auth/AllTests.php';
require 'Controller/AllTests.php';
require 'Helpers/AllTests.php';
require 'Orm/AllTests.php';
require 'Plumbing/AllTests.php';
require 'View/AllTests.php';



/**
 * All Of the unit tests...
 */
class AllUnitTests extends PHPUnit_Framework_TestSuite
{
	/**
	 * Creates the suite.
     * @return AllUnitTests
     */
	public static function suite()
	{
	    ini_set('display_errors', 'on');
	    ini_set('error_reporting', E_ALL);

		$suite = new self();
        $suite->addTestSuite('Auth_AllTests');
        $suite->addTestSuite('Controller_AllTests');
        $suite->addTestSuite('Helpers_AllTests');
		$suite->addTestSuite('Orm_AllTests');
        $suite->addTestSuite('Plumbing_AllTests');
        $suite->addTestSuite('View_AllTests');

		return $suite;
	}
}



?>