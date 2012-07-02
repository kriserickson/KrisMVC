<?php

/**
 * All Plumbing tests...
 */
class Plumbing_AllTests extends PHPUnit_Framework_TestSuite
{
	/**
	 * Creates the suite.
     * @return Plumbing_AllTests
     */
	public static function suite()
	{
		$suite = new self();

		$suite->addTestFile(__DIR__ . '/KrisDIContainerTest.php');
        $suite->addTestFile(__DIR__ . '/KrisCronManagerTest.php');

		return $suite;
	}
}