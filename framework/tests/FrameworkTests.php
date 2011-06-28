<?php

require('../../config/KrisConfig.php');

require('../../framework/lib/KrisController.php');
require('../../framework/lib/KrisDB.php');
require('../../framework/lib/KrisModel.php');
require('../../framework/lib/KrisDBView.php');
require('../../framework/lib/KrisView.php');


require_once 'KrisDB/AllTests.php';
require_once 'KrisView/AllTests.php';

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

		return $suite;
	}
}



?>