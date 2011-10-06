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
     * @var DBUserModel
     */
    private $_db;



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
        if ($this->_db->IsDirty())
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
        if ($this->_db->Retrieve('Email', $email))
        {
            return $this->LoginWithRecord($password);
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
            return $this->LoginWithRecord($password);
        }
        else
        {
            $this->_error = self::ERROR_INVALID_LOGIN;
        }

        return false;
    }

    /**
     * @param string $password
     * @return bool
     */
    protected  function LoginWithRecord($password)
    {
        // One hour
        if (time() - strtotime($this->_db->LastLogin) > 3600)
        {
            $this->_db->FailedLoginCount = 0;
        }

        if ($this->_db->FailedLoginCount > 5)
        {
            $this->_error = self::ERROR_TOO_MANY_INVALID_LOGINS;
        }
        else
        {
            /** @var $passwordCheck PasswordCheck */
            $passwordCheck = AutoLoader::$Container->get('PasswordCheck');
            $this->_db->Ip = $_SERVER['REMOTE_ADDR'];
            $this->_db->LastLogin = date('Y-m-d H:i:s');

            if ($passwordCheck->CheckPassword($password, $this->_db->PasswordHash))
            {
                $this->_user = new User($this->_db->UserId, $this->_db->DisplayName, $this->_db->Email, $this->_db->Data, $this->_db->Acl);
                $this->_db->FailedLoginCount = 0;
                $this->_db->Update();
                $this->LoginUserToSession();
                return true;
            }
            else
            {
                $this->_error = self::ERROR_INVALID_PASSWORD;
                $this->_db->FailedLoginCount = ($this->_db->FailedLoginCount + 1);
                $this->_db->Update();
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
        $this->_db->UserId =  $user_id;
        $this->_db->Data = $data();
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
            if ($this->_db->Retrieve('LoginName', $loginName))
            {
                // TODO: Add Suggestions...
                $this->_error = Auth::ERROR_LOGIN_NAME_ALREADY_EXISTS;
                return false;
            }
        }

        if ($this->_db->Retrieve('Email', $email))
        {
            $this->_error = Auth::ERROR_EMAIL_ALREADY_EXISTS;

            return false;
        }

        $this->_db->LoginName = $loginName;
        $this->_db->Email = $email;
        $this->_db->DisplayName = $displayName;
        $this->SetPassword($password);
        $this->_db->Create();

        return true;
    }


    /**
     * @return void
     */
    public function SaveData()
    {
        $this->_dbData = $this->_user->GetData();
    }

    /**
     * @return void
     */
    public function SaveAcl()
    {
        $this->_db->Acl = $this->_user->GetAcl();
    }


    /**
     * @param $password
     * @return void
     */
    public function SetPassword($password)
    {
        /** @var $passwordCheck PasswordCheck */
        $passwordCheck = AutoLoader::$Container->get('PasswordCheck');
        $hash = $passwordCheck->HashPassword($password);
        $this->_db->PasswordHash = $hash;
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

    /**
     *
     * @return bool|string
     * @param $email
     */
    public function GetPasswordReminderToken($email)
    {
        if (!($this->_db->Retrieve('Email', $email)))
        {
            $this->_error = Auth::ERROR_NO_USER_EMAIL;
            return false;
        }
        else
        {
            $guid = uniqid('epr', true);

            // Give 4 hours to reset the password
            $this->_db->RecoveryGuid = $guid;
            $this->_db->GuidExpire = date('Y-m-d h:i:s', time() + (60 * 60 * 4));

            $this->_db->Update();

            return $guid;
        }
    }

    /**
     * @param string $email
     * @return string
     */
    public function GetUsernameFromEmail($email)
    {
        if ($this->_db->Email != $email)
        {
            $this->_db->Retrieve('Email', $email);
        }
        return $this->_db->DisplayName;
    }

    /**
     * @param string $guid
     * @return bool
     */
    public function IsValidPasswordReminderGuid($guid)
    {
        if ($this->_db->Retrieve('RecoveryGuid', $guid))
        {
            return $this->ValidateGuidExpire($this->_db->GuidExpire);
        }
        else
        {
            return false;
        }
    }

    /**
     * @param string $guid
     * @param string $newPassword
     * @return bool
     */
    public function ChangePasswordWithPasswordReminderGuid($guid, $newPassword)
    {
        if ($this->_db->Retrieve('RecovertyGuid', $guid))
        {
            if ($this->ValidateGuidExpire($this->_db->GuidExpire))
            {
                $this->SetPassword($newPassword);
                $this->_db->RecoverGuid = '';
                $this->_db->Update();
                return true;
            }
            $this->_error = Auth::ERROR_EXPIRED_RECOVERY_TOKEN;
        }
        else
        {
            $this->_error = Auth::ERROR_UNKNOWN_RECOVERY_TOKEN;
        }
        return false;
    }

    /**
     * @param string $date
     * @return bool
     */
    private function ValidateGuidExpire($date)
    {
        return strtotime($date) > time();


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
    * @property string $RecoverGuid
    * @property string $GuidExpire
    */

    /**
     * return DBAuth
     */
    public function __construct()
    {
        parent::__construct('user_id', 'auth');
        $this->initializeRecordSet(array('UserId', 'LoginName', 'PasswordHash', 'FailedLoginCount', 'DisplayName', 'Email', 'Ip', 'LastLogin', 'Data', 'Acl', 'RecoveryGuid', 'GuidExpire'));
    }
}
