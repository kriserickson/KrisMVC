<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kris
 * Date: 8/6/11
 * Time: 1:59 PM
 * To change this template use File | Settings | File Templates.
 */

/**
 * @package auth
 */
interface PasswordCheck {
/**
     * @param string $password
     * @return string
     */
	public function HashPassword($password);


    /**
     * @param string $password
     * @param string $stored_hash
     * @return bool
     */
	public function CheckPassword($password, $stored_hash);
}
