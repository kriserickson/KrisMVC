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
 * Default session instance, implemented through the _SESSION object...
 */
class Session
{
    /**
     * @var Session
     */
    static $_instance;

    /**
     * Create the session
     */
    private function __construct()
    {
        $params = session_get_cookie_params();
        $domain = isset(KrisConfig::$SESSION_DOMAIN) ? KrisConfig::$SESSION_DOMAIN: $params["domain"];
        session_set_cookie_params($params['lifetime'], $params["path"], $domain, $params["secure"], $params["httponly"]);
        session_start();
    }

    /**
     * @param Session $session | Used for mocking...
     * @return Session
     */
    public static function instance(Session $session = null)
    {
        if (!is_null($session))
        {
            self::$_instance = $session;
        }
        if (!isset(self::$_instance))
        {
            self::$_instance = new Session();
        }

        return self::$_instance;
    }

    /**
     * @param int $expire - set when the cache expires...
     * @return int
     */
    public function CacheExpire($expire)
    {
        return session_cache_expire($expire);
    }

    /**
     * @return bool
     */
    public function Destroy()
    {
        $_SESSION = array();

        if (ini_get("session.use_cookies"))
        {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        return session_destroy();
    }

    /**
     * @param null $id
     * @return null|string
     */
    public function id($id = null)
    {
        if (is_null($id))
        {
            return session_id();
        }
        session_id($id);
        return $id;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function Get($name, $default = '')
    {
        return (isset($_SESSION[$name])) ? $_SESSION[$name] : $default;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return Session
     */
    public function Set($name, $value)
    {
        $_SESSION[$name] = $value;
        return $this;
    }


}
