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
    private $_error;

    /**
     * return Auth_DB
     */
    public function __construct()
    {
        $this->_db = new DBAuth;
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
            $passwordHash = new PasswordHash(8, FALSE);
            $passwordHash->CheckPassword($password, $ret->Get('Password'));

        }
    }

    /**
     * @throws Exception
     * @return bool
     */
    public function Logout()
    {
        $this->_user = null;
        $this->_session->destroy();
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


    /**
     * @return string
     */
    public function Error()
    {
        return $this->_error;
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
        parent::__construct('auth', 'user_id');
        $this->initializeRecordSet(array('UserId', 'LoginName', 'PasswordHash', 'FailedLoginCount', 'DisplayName', 'Email', 'Ip', 'LastLogin', 'Data', 'Acl'));
    }
}
