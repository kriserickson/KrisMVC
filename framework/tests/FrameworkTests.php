<?php

require __DIR__.'../../config/KrisConfig.php';

require __DIR__.'../../framework/lib/KrisController.php';
require __DIR__.'../../framework/lib/KrisDB.php';
require __DIR__.'../../framework/lib/KrisModel.php';
require __DIR__.'../../framework/lib/KrisCrudModel.php';
require __DIR__.'../../framework/lib/KrisDBView.php';
require __DIR__.'../../framework/lib/KrisView.php';


require 'KrisDB/AllTests.php';
require 'KrisView/AllTests.php';
require 'KrisController/AllTests.php';

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

		$suite->addTestSuite('KrisDB_AllTests');
        $suite->addTestSuite('KrisController_AllTests');
        $suite->addTestSuite('KrisView_AllTests');

		return $suite;
	}
}



?>