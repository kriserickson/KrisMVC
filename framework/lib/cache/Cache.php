<?php
/**
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
abstract class Cache
{
    /**
     * @var \Cache
     */
    private static $instance;

    const ONE_DAY = 86400;


    /**
     * @static
     * @throws Exception
     * @param \Cache $instance
     * @return \Cache
     */
    public static function instance(Cache $instance = null)
    {
        if (!is_null($instance))
        {
            self::$instance = $instance;
        }

        if (!isset(self::$instance))
        {
            switch (KrisConfig::$CACHE_TYPE)
            {
                case KrisConfig::CACHE_TYPE_APC :
                    self::$instance = new ApcCache();
                    break;
                case KrisConfig::CACHE_TYPE_FILE :
                    self::$instance = new FileCache();
                    break;
                case KrisConfig::CACHE_TYPE_DB :
                    self::$instance = new DBCache();
                    break;
                case KrisConfig::CACHE_TYPE_MEMCACHE :
                    self::$instance = new MemcacheCache();
                    break;
                default:
                    throw new Exception('Unsupported cache type');
            }
        }
        return self::$instance;
    }

    /**
     * @abstract
     * @param string $key
     * @param string $value
     * @param int $ttl
     * @return void
     */
    abstract public function Store($key, $value, $ttl = 0);

    /**
     * @abstract
     * @param string $key
     * @param string $value
     * @param int $ttl
     * @return void
     */
    abstract public function Add($key, $value, $ttl = 0);

    /**
     * @abstract
     * @param string $key
     * @param string $default
     * @return string
     */
    abstract public function Fetch($key, $default = '');

    /**
     * @abstract
     * @param string $key
     * @return boolean
     */
    abstract public function Delete($key);

    /**
     * @abstract
     * @return void
     */
    abstract public function ClearCache();

}

