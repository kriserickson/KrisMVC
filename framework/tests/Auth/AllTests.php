<?php

/**
 * All Controller tests...
 */
class Auth_AllTests extends PHPUnit_Framework_TestSuite
{
	/**
	 * Creates the suite.
     * @return Auth_AllTests
     */
	public static function suite()
	{
		$suite = new self();

		$suite->addTestFile(__DIR__ . '/PasswordHashTest.php');

		return $suite;
	}
}