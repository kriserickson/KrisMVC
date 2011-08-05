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
 * Auth_DB is a database authentication layer...
 */
class Auth_DB extends Auth
{


    /**
     * @var \DBAuth
     */
    private $_db;


    /**
     * return Auth_DB
     */
    public function __construct()
    {
        parent::__construct();
        $this->_db = new DBAuth;
    }

    /**
     * @param string $loginName
     * @param string $password
     * @return bool
     */
    public function LoginWithEmail($email, $password)
    {
        $ret = $this->_db->Retrieve('Email', $email);
        if ($ret)
        {
            return $this->LoginWithRecord($ret, $password);
        }
        else
        {
            $this->_error = self::ERROR_INVALID_LOGIN;
        }

        return false;
    }

    /**
     * @param string $loginName
     * @param string $password
     * @return bool
     */
    public function Login($loginName, $password)
    {
        $ret = $this->_db->Retrieve('LoginName', $loginName);
        if ($ret)
        {
            return $this->LoginWithRecord($ret, $password);
        }
        else
        {
            $this->_error = self::ERROR_INVALID_LOGIN;
        }

        return false;
    }

    public function LoginWithRecord($ret, $password)
    { // One hour
        if (time() - strtotime($ret->Get('LastLogin')) > 3600)
        {
            $ret->Set('FailedLoginCount', 0);
        }

        if ($ret->Get('FailedLoginCount') > 5)
        {
            $this->_error = self::ERROR_TOO_MANY_INVALID_LOGINS;
        }
        else
        {
            $passwordHash = new PasswordHash(8, true);
            if ($passwordHash->CheckPassword($password, $ret->Get('PasswordHash')))
            {
                $this->_user = new User($ret->Get('UserId'), $ret->Get('DisplayName'), $ret->Get('Email'), $ret->Get('Data'), $ret->Get('Acl'));
                $ret->Set('FailedLoginCount', 0);
                $ret->Set('Ip', $_SERVER['REMOTE_ADDR']);
                $ret->Update();
                $this->LoginUserToSession();
                return true;
            }
            else
            {
                $this->_error = self::ERROR_INVALID_PASSWORD;
                $ret->Set('FailedLoginCount', $ret->Get('FailedLoginCount') + 1);
                $ret->Set('Ip', $_SERVER['REMOTE_ADDR']);
            }
        }
        return false;
    }

    /**
     * @param int $user_id
     * @param string $data
     * @return void
     */
    public function SaveUserData($user_id, $data)

    {
        $this->_db->Set('UserId', $user_id);
        $this->_db->Set('Data', $data());
        $this->_db->Update();
    }

    /**
     * @throws Exception
     * @return bool
     */
    public function Logout()
    {
        $this->_user = null;
        $this->_session->Destroy();
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
        $ret = $this->_db->Retrieve('LoginName', $loginName);
        if ($ret)
        {
            $this->_error = 'LoginName '.$loginName.' already exists';
            // TODO: Add Suggestions...
            return false;
        }
        $ret = $this->_db->Retrieve('Email', $email);
        if ($ret)
        {
            $this->_error = 'Email '.$email.' has already been used';
            return false;
        }

        $this->_db->Set('LoginName', $loginName)->Set('Password', $password)->Set('Email', $email)->Set('DisplayName', $displayName);
        $this->_db->Create();
        return true;

    }




}

/**
 * Database Class for
 */
class DBAuth extends KrisModel
{
    /**
     * return DBAuth
     */
    public function __construct()
    {
        parent::__construct('user_id', 'auth');
        $this->initializeRecordSet(array('UserId', 'LoginName', 'PasswordHash', 'FailedLoginCount', 'DisplayName', 'Email', 'Ip', 'LastLogin', 'Data', 'Acl'));
    }
}
