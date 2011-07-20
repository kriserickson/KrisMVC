<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Authentication class...
 *
 * @throws Exception
 *
 */
class Auth
{
    /**
     * @var \Auth
     */
    private static $_instance;

    /**
     * @var \User
     */
    protected $_user = null;

    /**
     * @var \Session
     */
    protected  $_session;


    /**
     * @static
     * @param Auth $auth - used for mocking...
     * @return \Auth
     */
    public static function instance(Auth $auth = null)
    {
        if (!is_null($auth))
        {
            self::$_instance = $auth;
        }
        if (!isset(self::$_instance))
        {
            $auth_type = 'Auth_'.KrisConfig::$AUTH_TYPE;
            self::$_instance = new $auth_type();
        }

        return self::$_instance;
    }

    /**
     * @return bool
     */
    public function IsLoggedIn()
    {
        return !is_null($this->_user);
    }

    /**
     * @param string $loginName
     * @param string $password
     * @return bool
     */
    public function Login($loginName, $password)
    {
        throw new Exception('Login not implemented in '.get_class($this));
    }

    /**
     * @throws Exception
     * @return bool
     */
    public function Logout()
    {
        throw new Exception('Logout not implemented in '.get_class($this));
    }

    /**
     * @param string $loginName
     * @param string $password
     * @param string $email
     * @param string $displayName
     * @return bool
     */
    public function AddUser($loginName, $password, $email, $displayName)
    {

    }

    /**
     * @return User
     */
    public function User()
    {
        return $this->_user;
    }

}

?>