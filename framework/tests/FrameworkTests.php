<?php

require dirname(__FILE__) . '/../../config/KrisConfig.php';

require dirname(__FILE__) . '/../../framework/lib/auth/PasswordHash.php';
require dirname(__FILE__) . '/../../framework/lib/controller/KrisController.php';
require dirname(__FILE__) . '/../../framework/lib/orm/KrisDB.php';
require dirname(__FILE__) . '/../../framework/lib/orm/KrisModel.php';
require dirname(__FILE__) . '/../../framework/lib/orm/KrisCrudModel.php';
require dirname(__FILE__) . '/../../framework/lib/orm/KrisDBView.php';
require dirname(__FILE__) . '/../../framework/lib/view/KrisView.php';


require 'Auth/AllTests.php';
require 'Controller/AllTests.php';
require 'Orm/AllTests.php';
require 'View/AllTests.php';


/**
 * All Of the unit tests...
 */
class AllUnitTests extends PHPUnit_Framework_TestSuite
{
	/**
	 * Creates the suite.
     * @return \AllUnitTests
     */
	public static function suite()
	{
	    ini_set('display_errors', 'on');
	    ini_set('error_reporting', E_ALL);

		$suite = new self();
        $suite->addTestSuite('Auth_AllTests');
        $suite->addTestSuite('Controller_AllTests');
		$suite->addTestSuite('Orm_AllTests');
        $suite->addTestSuite('View_AllTests');

		return $suite;
	}
}



?>