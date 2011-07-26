<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
     * @param int $userId
     * @param string $displayName
     * @param string $email
     * @param string $data
     * @param int $acl
     * @return \User
     *
     */
    function __construct($userId, $displayName, $email, $data, $acl)
    {
        $this->_userId = $userId;
        $this->_displayName = $displayName;
        $this->_email = $email;
        $this->_data = unserialize($data);
        $this->_acl = $acl;
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
     * @return string
     */
    function DisplayName()
    {
        return $this->_displayName;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function GetUserData($name, $default)
    {
        if (isset($this->_data[$name]))
        {
            return $this->_data[$name];
        }
        return $default;
    }

    /**
     * @param $name
     * @param $value
     * @return void
     */
    public function SetUserData($name, $value)
    {
        $this->_data[$name] = $value;
        Auth::instance()->SaveUserData($this->_userId, serialize($this->_data));
    }

    /**
     * @param int $acl
     * @return bool
     */
    public function HasAcl($acl)
    {
        return $acl & $this->_acl;
    }

    /**
     * @param int $acl
     * @return bool
     */
    public function HasAclOrGreater($acl)
    {
        return $this->_acl >= $acl;
    }



}
