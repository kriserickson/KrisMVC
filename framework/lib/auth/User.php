<?php
/**
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @package auth
 */
class User
{
    /**
     * @var int
     */
    private $_userId;

    /**
     * @var string
     */
    private $_displayName;

    /**
     * @var string
     */
    private $_email;

    /**
     * @var array
     */
    private $_data;

    /**
     * @var int - bitfield
     */
    private $_acl;

    /**
     * @var boolean
     */
    protected $_dataChanged;

    /**
     * @var bool
     */
    protected $_aclChanged;

    /**
     * @var int
     */
    protected $_lastLoginTime;


    /**
     * @param int $userId
     * @param string $displayName
     * @param string $email
     * @param string $data
     * @param int $acl
     * @param int $loginTime
     * @return User
     */
    function __construct($userId, $displayName, $email, $data, $acl, $loginTime)
    {
        $this->_userId = $userId;
        $this->_displayName = $displayName;
        $this->_email = $email;
        $this->_data = unserialize($data);
        $this->_acl = $acl;
        $this->_lastLoginTime = $loginTime;
    }


    /**
     * Save UserData and Acl's if they are changed...
     */
    public function __destruct()
    {
        if ($this->_dataChanged)
        {
            Auth::instance()->SaveData();
        }
        if ($this->_aclChanged)
        {
            Auth::instance()->SaveAcl();
        }
    }

    /**
     * @return int
     */
    function UserId()
    {
        return $this->_userId;
    }

    /**
     * @return string
     */
    function Email()
    {
        return $this->_email;
    }

    /**
     * @return int
     */
    function LastLoginTime()
    {
        return $this->_lastLoginTime;
    }


    /**
     * @return string
     */
    function UserName()
    {
        return $this->_displayName;
    }

    /**
     * @throws Exception
     * @param $name
     * @param $value
     * @return void
     */
    public function SetUserData($name, $value)
    {
        $this->_data[$name] = $value;
        $this->_dataChanged = true;
    }

    /**
     * @param string $name
     * @return void
     */
    public function UnsetUserData($name)
    {
        unset($this->_data[$name]);
        $this->_dataChanged = true;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function GetUserData($name, $default = false)
    {
        if (isset($this->_data[$name]))
        {
            return $this->_data[$name];
        }
        return $default;
    }

    /**
     * @param int $acl
     * @return void
     */
    public function SetAcl($acl)
    {
        $this->_acl |= $acl;
        $this->_aclChanged = true;
    }

    /**
     * @param int $acl
     * @return void
     */
    public function RemoveAcl($acl)
    {
        $this->_acl = $this->_acl & ~$acl;
        $this->_aclChanged = true;
    }

    /**
     * @param int $acl ACL_ type
     * @return int
     */
    public function HasAcl($acl)
    {
        return $this->_acl & $acl;
    }


    /**
     * @param int $acl
     * @return bool
     */
    public function HasAclOrGreater($acl)
    {
        return $this->_acl >= $acl;
    }

    /**
     * @access internal
     * @return int
     */
    public function GetAcl()
    {
        return $this->_acl;
    }

    /**
     * @access internal
     * @return array
     */
    public function GetData()
    {
        return $this->_data;
    }



}
