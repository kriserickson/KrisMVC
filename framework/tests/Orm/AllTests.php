<?php

/**
 * All DB Tests..
 */
class Orm_AllTests extends PHPUnit_Framework_TestSuite
{
	/**
	 * Creates the suite.
     * @return Orm_AllTests
     */
	public static function suite()
	{
		$suite = new self();

		$suite->addTestFile(__DIR__ . '/KrisDBTest.php');
        $suite->addTestFile(__DIR__ . '/KrisDBViewTest.php');
        $suite->addTestFile(__DIR__ . '/KrisModelTest.php');
        $suite->addTestFile(__DIR__ . '/KrisCrudModelTest.php');

		return $suite;
	}
}