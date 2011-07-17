<?php

/**
 * All DB Tests..
 */
class Orm_AllTests extends PHPUnit_Framework_TestSuite
{
	/**
	 * Creates the suite.
     * @return \AspSystem_AllTests
     */
	public static function suite()
	{
		$suite = new self();

		$suite->addTestFile(dirname(__FILE__) . '/KrisDBTest.php');
        $suite->addTestFile(dirname(__FILE__) . '/KrisDBViewTest.php');
        $suite->addTestFile(dirname(__FILE__) . '/KrisModelTest.php');
        $suite->addTestFile(dirname(__FILE__) . '/KrisCrudModelTest.php');

		return $suite;
	}
}