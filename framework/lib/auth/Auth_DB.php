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
     * @var \DBUserModel
     */
    private $_db;


    /**
     * @var bool
     */
    private $_dataChanged;


    /**
     * return Auth_DB
     */
    public function __construct()
    {
        parent::__construct();
        $this->_db = new DBUserModel;
    }

    /**
     *
     */
    public function __destruct()
    {
        if ($this->_dataChanged)
        {
            $this->_db->Update();
        }
    }

    /**
     * @param $email
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

    /**
     * @param KrisModel $record
     * @param string $password
     * @return bool
     */
    protected  function LoginWithRecord($record, $password)
    {
        // One hour
        if (time() - strtotime($record->Get('LastLogin')) > 3600)
        {
            $record->Set('FailedLoginCount', 0);
        }

        if ($record->Get('FailedLoginCount') > 5)
        {
            $this->_error = self::ERROR_TOO_MANY_INVALID_LOGINS;
        }
        else
        {
            /** @var $passwordCheck PasswordCheck */
            $passwordCheck = AutoLoader::$Container->get('PasswordCheck');
            $record->Set('Ip', $_SERVER['REMOTE_ADDR']);
            $record->Set('LastLogin', date('Y-m-d h:i:s'));

            if ($passwordCheck->CheckPassword($password, $record->Get('PasswordHash')))
            {
                $this->_user = new User($record->Get('UserId'), $record->Get('DisplayName'), $record->Get('Email'), $record->Get('Data'), $record->Get('Acl'));
                $record->Set('FailedLoginCount', 0);
                $record->Update();
                $this->LoginUserToSession();
                return true;
            }
            else
            {
                $this->_error = self::ERROR_INVALID_PASSWORD;
                $record->Set('FailedLoginCount', $record->Get('FailedLoginCount') + 1);
                $record->Update();
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
     * @param string $email
     * @param string $password
     * @param string $displayName
     * @param bool $requireLoginName
     * @return bool
     */
    protected function AddUserRecord($loginName, $email, $password, $displayName, $requireLoginName)
    {
        if ($requireLoginName)
        {
            $ret = $this->_db->Retrieve('LoginName', $loginName);
            if ($ret)
            {
                // TODO: Add Suggestions...
                $this->_error = Auth::ERROR_LOGIN_NAME_ALREADY_EXISTS;
                return false;
            }
        }

        $ret = $this->_db->Retrieve('Email', $email);
        if ($ret)
        {
            $this->_error = Auth::ERROR_EMAIL_ALREADY_EXISTS;

            return false;
        }

        /** @var $passwordCheck PasswordCheck */
        $passwordCheck = AutoLoader::$Container->get('PasswordCheck');
        $hash = $passwordCheck->HashPassword($password);

        $this->_db->Set('LoginName', $loginName)->Set('PasswordHash', $hash)->Set('Email', $email)->Set('DisplayName', $displayName);
        $this->_db->Create();

        return true;
    }


    /**
     * @return void
     */
    public function SaveData()
    {
        $this->Set('Data', $this->_user->GetData());
    }

    /**
     * @return void
     */
    public function SaveAcl()
    {
        $this->Set('Acl', $this->_user->GetAcl());
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    protected function Set($name, $value)
    {
        $this->_db->Set($name, $value);
        $this->_dataChanged = true;
    }

    /**
     * @param $password
     * @return void
     */
    public function SetPassword($password)
    {
        $this->Set('Password', $password);
    }

    /**
     * @param int $startPosition
     * @param int $pageSize
     * @param string $orderBy
     * @return array of KrisModel
     */
    public function GetUsers($startPosition, $pageSize, $orderBy = 'Email')
    {
        return $this->_db->RetrieveMultiple(array(), array(), false, $pageSize, $startPosition, $orderBy);
    }

    /**
     * @param int $searchType
     * @param string $search
     * @param int $startPosition
     * @param int $pageSize
     * @param string $orderBy
     * @return array
     */
    public function SearchUsers($searchType, $search, $startPosition, $pageSize, $orderBy = 'Email')
    {
        $searchField = $this->GetSearchField($searchType);

        return $this->_db->RetrieveMultiple(array($searchField), array($search), true, $pageSize, $startPosition, $orderBy);
    }

    /**
     * @throws Exception
     * @param int $searchType
     * @return string
     */
    protected function GetSearchField($searchType)
    {
        if ($searchType == Auth::SEARCH_TYPE_USERNAME)
        {
            $searchField = 'LoginName';
            return $searchField;
        }
        else if ($searchType == Auth::SEARCH_TYPE_EMAIL)
        {
            $searchField = 'Email';
            return $searchField;
        }
        else
        {
            throw new Exception('Invalid searchType');
        }
    }

    /**
     * @param $searchType
     * @param $search
     * @return int
     */
    public function TotalUsers($searchType, $search)
    {
        if (strlen($search) > 0)
        {
            $searchField = array($this->GetSearchField($searchType));
            $search = array($search);
        }
        else
        {
            $searchField = array();
            $search = array();
        }

        return $this->_db->TotalRecords($searchField, $search, true);
    }
}

/**
 * Database Class for
 */
class DBUserModel extends KrisModel
{
    /**
    * @property int $UserId
    * @property string $LoginName
    * @property string $PasswordHash
    * @property int $FailedLoginCount
    * @property string $DisplayName
    * @property string $Email
    * @property string $Ip
    * @property datetime $LastLogin
    * @property string $Data
    * @property int $Acl
    */

    /**
     * return DBAuth
     */
    public function __construct()
    {
        parent::__construct('user_id', 'auth');
        $this->initializeRecordSet(array('UserId', 'LoginName', 'PasswordHash', 'FailedLoginCount', 'DisplayName', 'Email', 'Ip', 'LastLogin', 'Data', 'Acl'));
    }
}
