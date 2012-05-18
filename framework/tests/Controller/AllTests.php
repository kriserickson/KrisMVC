<?php

/**
 * All Controller tests...
 */
class Controller_AllTests extends PHPUnit_Framework_TestSuite
{
	/**
	 * Creates the suite.
     * @return Controller_AllTests
     */
	public static function suite()
	{
		$suite = new self();

		$suite->addTestFile(__DIR__ . '/KrisRouterTest.php');
        $suite->addTestFile(__DIR__ . '/RequestTest.php');
        
		return $suite;
	}
}
?>