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
    // Possible auth errors
    const ERROR_INVALID_LOGIN = 1;
    const ERROR_INVALID_PASSWORD = 2;
    const ERROR_TOO_MANY_INVALID_LOGINS = 3;

    // ACLs
    const ACL_NONE = 0;
    const ACL_GUEST = 1;
    const ACL_READ = 2;
    const ACL_WRITE = 4;
    const ACL_EDIT = 8;
    const ACL_ADMIN = 16;
    const ACL_ROOT = 32;
    const ACL_DEVELOPER = 64;

    /**
     * See above constants for errors...
     *
     * @var int
     */
    protected  $_error;


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

    protected function __construct()
    {
        $this->_session = Session::instance();
        if (!is_null($this->_session->Get('user', null)))
        {
            $this->_user = $this->_session->Get('user');
        }
    }


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
        throw new Exception('AddUser not implemented in '.get_class($this));
    }

    /**
     * @return User
     */
    public function User()
    {
        return $this->_user;
    }



    /**
     * @return int
     */
    public function Error()
    {
        return $this->_error;
    }

    protected  function LoginUserToSession()
    {
        $this->_session->Set('user', $this->_user);
    }

    /**
     * @throws Exception
     * @param $user_id
     * @param $data
     * @return void
     */
    public function SaveUserData($user_id, $data)
    {
        throw new Exception('SaveUserData not implemented in '.get_class($this));
    }

}

?>