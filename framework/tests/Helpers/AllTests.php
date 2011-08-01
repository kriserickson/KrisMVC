<?php

/**
 * All Helpers Tests..
 */
class Helpers_AllTests extends PHPUnit_Framework_TestSuite
{
	/**
	 * Creates the suite.
     * @return \AspSystem_AllTests
     */
	public static function suite()
	{
		$suite = new self();

		$suite->addTestFile(__DIR__ . '/HtmlHelpersTest.php');

		return $suite;
	}
}