<?php

class KrisController_AllTests extends PHPUnit_Framework_TestSuite
{
	/**
	 * Creates the suite.
     * @return \AspSystem_AllTests
     */
	public static function suite()
	{
		$suite = new self();

		$suite->addTestFile(dirname(__FILE__) . '/KrisControllerTest.php');

		return $suite;
	}
}