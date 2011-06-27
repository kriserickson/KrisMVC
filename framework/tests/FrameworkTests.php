<?php

require('../../config/KrisConfig.php');

require('../../framework/KrisController.php');
require('../../framework/KrisDB.php');
require('../../framework/KrisModel.php');
require('../../framework/KrisDBView.php');
require('../../framework/KrisView.php');


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