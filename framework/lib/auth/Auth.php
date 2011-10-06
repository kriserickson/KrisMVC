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
    const ERROR_NO_USER_EMAIL = 13;
    const ERROR_EXPIRED_RECOVERY_TOKEN = 14;
    const ERROR_UNKNOWN_RECOVERY_TOKEN = 15;

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
     * @param string $displayName - Optionals
     * @param bool $requireLoginName - Whether login name is required
     * @param bool $loginUser
     * @return bool
     */
    public function AddUser($loginName, $email, $password, $confirmPassword, $displayName = '', $requireLoginName = true, $loginUser = false)
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
        else if (!$this->IsValidPassword($password))
        {
            return false;
        }
        else if ($password != $confirmPassword)
        {
            $this->_error = Auth::ERROR_CONFIRM_PASSWORD_DOES_NOT_MATCH_PASSWORD;
            return false;
        }

        $ret = $this->AddUserRecord($loginName, $email, $password, $displayName, $requireLoginName);

        if ($ret && $loginUser)
        {
            $this->LoginWithEmail($email, $password);
        }

        return $ret;
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
    public function IsValidPassword($password)
    {
        $error = 0;

        if( strlen($password) < 8 )
        {
            $error = Auth::ERROR_PASSWORD_TOO_SHORT;
        }
        else if( !preg_match("#[0-9]+#", $password) )
        {
            $error = Auth::ERROR_PASSWORD_MUST_INCLUDE_ONE_NUMBER;
        }
        else if( !preg_match("#[a-z]+#", $password) )
        {
            $error =  Auth::ERROR_PASSWORD_MUST_INCLUDE_ONE_LETTER;

        }
        else if( !preg_match("#[A-Z]+#", $password) )
        {
            $error = Auth::ERROR_PASSWORD_MUST_INCLUDE_ONE_CAPITAL_LETTER;

        }
        else if( !preg_match("#\\W+#", $password) )
        {
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
     * @abstract
     * @throws Exception
     * @param $email
     * @param $password
     * @return void
     */
    public abstract function LoginWithEmail($email, $password);

    /**
     * @abstract
     * @param string $loginName
     * @param string $password
     * @return bool
     */
    public abstract function Login($loginName, $password);

    /**
     * @abstract
     * @throws Exception
     * @return bool
     */
    public abstract function Logout();

    /**
     * @abstract
     * @param string $email
     * @return bool|string
     */
    public abstract function GetPasswordReminderToken($email);


    /**
     * @abstract
     * @param string $guid
     * @return bool
     */
    public abstract function IsValidPasswordReminderGuid($guid);

    /**
     * @param string $guid
     * @param string $newPassword
     * @return bool
     */
    public abstract function ChangePasswordWithPasswordReminderGuid($guid, $newPassword);

    /**
     * @abstract
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
     * @param string $email
     */
    public abstract function GetUsernameFromEmail($email);

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

    public static function GetFriendlyAuthError($error_code)
    {
        switch ($error_code)
        {
            case Auth::ERROR_INVALID_PASSWORD:
            case Auth::ERROR_INVALID_LOGIN:
                $error = 'Invalid Username or Password';
                break;
            case Auth::ERROR_CONFIRM_PASSWORD_DOES_NOT_MATCH_PASSWORD:
                $error = "Passwords don't match";
                break;
            case Auth::ERROR_EMAIL_ALREADY_EXISTS:
                $error = 'Email Already Exists';
                break;
            case Auth::ERROR_TOO_MANY_INVALID_LOGINS:
                $error = 'Too many invalid logins, try again in an hour';
                break;
            case Auth::ERROR_EXPIRED_RECOVERY_TOKEN:
                $error = 'The recovery token has expired';
                break;
            case Auth::ERROR_INVALID_EMAIL:
                $error = 'Invalid email address';
                break;
            case Auth::ERROR_INVALID_LOGIN:
                $error = 'Invalid login name';
                break;
            case Auth::ERROR_LOGIN_NAME_ALREADY_EXISTS:
                $error = 'Login name already exists';
                break;
            case Auth::ERROR_NO_USER_EMAIL:
                $error = 'No user email given';
                break;
            case Auth::ERROR_PASSWORD_MUST_INCLUDE_ONE_CAPITAL_LETTER:
                $error = 'Invalid password.  Password must include one capital letter';
                break;
            case Auth::ERROR_PASSWORD_MUST_INCLUDE_ONE_LETTER:
                $error = 'Invalid password.  Password must include one letter';
                break;
            case Auth::ERROR_PASSWORD_MUST_INCLUDE_ONE_NUMBER:
                $error = 'Invalid password.  Password must include one number';
                break;
            case Auth::ERROR_PASSWORD_MUST_INCLUDE_ONE_SYMBOL:
                $error = 'Invalid password.  Password must include one symbol';
                break;
            case Auth::ERROR_PASSWORD_TOO_SHORT:
                $error = 'Invalid password.  Password must be at least 8 characters';
                break;
            case Auth::ERROR_TOO_MANY_INVALID_LOGINS:
                $error = 'Too many invalid logins, try again in an hour';
                break;
            case Auth::ERROR_UNKNOWN_RECOVERY_TOKEN:
                $error = 'Unknown recover token';
                break;
            default:
                $error = 'Unknown error';

        }

        return $error;
    }


}

?>