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
abstract class Auth
{
    // Possible auth errors
    const ERROR_INVALID_LOGIN = 1;
    const ERROR_INVALID_PASSWORD = 2;
    const ERROR_TOO_MANY_INVALID_LOGINS = 3;
    const ERROR_PASSWORD_TOO_SHORT = 4;
    const ERROR_PASSWORD_MUST_INCLUDE_ONE_NUMBER = 5;
    const ERROR_PASSWORD_MUST_INCLUDE_ONE_LETTER = 6;
    const ERROR_PASSWORD_MUST_INCLUDE_ONE_CAPITAL_LETTER = 7;
    const ERROR_PASSWORD_MUST_INCLUDE_ONE_SYMBOL = 8;
    const ERROR_EMAIL_ALREADY_EXISTS = 9;
    const ERROR_LOGIN_NAME_ALREADY_EXISTS = 10;
    const ERROR_INVALID_EMAIL = 11;
    const ERROR_CONFIRM_PASSWORD_DOES_NOT_MATCH_PASSWORD = 12;

    // ACLs
    const ACL_NONE = 0;
    const ACL_GUEST = 1;
    const ACL_READ = 2;
    const ACL_WRITE = 4;
    const ACL_EDIT = 8;
    const ACL_ADMIN = 16;
    const ACL_ROOT = 32;
    const ACL_DEVELOPER = 64;

    // Search Types for find users...
    const SEARCH_TYPE_USERNAME = 0;
    const SEARCH_TYPE_EMAIL = 1;

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
    protected $_session;


    /**
     * Load the user...
     */
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



    /**
     * @return void
     */
    protected function LoginUserToSession()
    {
        $this->_session->Set('user', $this->_user);
    }

    /**
     * @param string $loginName
     * @param string $email
     * @param string $password
     * @param string $confirmPassword
     * @param string $displayName
     * @param bool $requireLoginName
     * @return bool
     */
    public function AddUser($loginName, $email, $password, $confirmPassword, $displayName, $requireLoginName)
    {
        if ($requireLoginName && strlen($loginName) == 0)
        {
            $this->_error = Auth::ERROR_INVALID_LOGIN;
            return false;
        }
        else if (strlen($email) == 0 || !$this->isValidEmail($email))
        {
            $this->_error = Auth::ERROR_INVALID_EMAIL;

            return false;
        }
        else if (!$this->isValidPassword($password))
        {
            return false;
        }
        else if ($password != $confirmPassword)
        {
            $this->_error = Auth::ERROR_CONFIRM_PASSWORD_DOES_NOT_MATCH_PASSWORD;
            return false;
        }

        return $this->AddUserRecord($loginName, $email, $password, $displayName, $requireLoginName);
    }

    /**
     * @param string $email
     * @return bool
     */
    private function isValidEmail($email)
    {
       $atIndex = strrpos($email, "@");

       if (is_bool($atIndex) && !$atIndex)
       {
          return false;
       }
       else
       {
          $domain = substr($email, $atIndex+1);
          $username = substr($email, 0, $atIndex);
          $localLen = strlen($username);
          $domainLen = strlen($domain);
          if ($localLen < 1 || $localLen > 64 || $domainLen < 1 || $domainLen > 255 || $username[0] == '.' || $username[$localLen-1] == '.'
              || preg_match('/\\.\\./', $username) || !preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain) || preg_match('/\\.\\./', $domain))
          {
             // Invalid email
             return false;
          }
          else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$username)))
          {
             // character not valid in local part unless local part is quoted
             if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$username)))
             {
                return  false;
             }
          }
          if (!(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
          {
             // domain not found in DNS
             return false;
          }
       }

       return true;
    }

    /**
     * @param string $password
     * @return bool
     */
    private function isValidPassword($password)
    {
        $error = 0;

        if( strlen($password) < 8 ) {
            $error = Auth::ERROR_PASSWORD_TOO_SHORT;
        }
        else if( !preg_match("#[0-9]+#", $password) ) {
            $error = Auth::ERROR_PASSWORD_MUST_INCLUDE_ONE_NUMBER;
        }
        else if( !preg_match("#[a-z]+#", $password) ) {
            $error =  Auth::ERROR_PASSWORD_MUST_INCLUDE_ONE_LETTER;

        }
        else if( !preg_match("#[A-Z]+#", $password) ) {
            $error = Auth::ERROR_PASSWORD_MUST_INCLUDE_ONE_CAPITAL_LETTER;

        }
        else if( !preg_match("#\\W+#", $password) ) {
            $error = Auth::ERROR_PASSWORD_MUST_INCLUDE_ONE_SYMBOL;
        }


        if ($error != 0)
        {
            $this->_error = $error;
            return false;
        }

        return true;

    }



    /**
     * @throws Exception
     * @param $email
     * @param $password
     * @return void
     */
    public abstract function LoginWithEmail($email, $password);

    /**
     * @param string $loginName
     * @param string $password
     * @return bool
     */
    public abstract function Login($loginName, $password);

    /**
     * @throws Exception
     * @return bool
     */
    public abstract function Logout();


    /**
     * @param string $loginName
     * @param string $email
     * @param string $password
     * @param string $displayName
     * @param bool $requireLoginName
     */
    protected abstract function AddUserRecord($loginName, $email, $password, $displayName, $requireLoginName);

    /**
     * @abstract Saves the User Data
     * @return void
     */
    public abstract function SaveData();

    /**
     * @abstract
     * @param $password
     * @return void
     */
    public abstract  function SetPassword($password);


    /**
     * @abstract
     * @return void
     */
    public abstract function SaveAcl();


    /**
     * @abstract
     * @param int $startPosition
     * @param int $pageSize
     * @param string $orderBy
     * @return array
     */
    public abstract function GetUsers($startPosition, $pageSize, $orderBy = 'Email');

    /**
     * @abstract
     * @param int $searchType
     * @param string $search
     * @param int $startPosition
     * @param int $pageSize
     * @param string $orderBy
     * @return array
     */
    public abstract function SearchUsers($searchType, $search, $startPosition, $pageSize, $orderBy = 'Email');

    /**
     * @abstract
     * @param $searchType
     * @param $search
     * @return void
     */
    public abstract function TotalUsers($searchType, $search);


}

?>