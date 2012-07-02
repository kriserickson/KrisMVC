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
 * DBCache
 */
class ApcCache extends Cache
{

    /**
     * @param string $key
     * @param string $value
     * @param int $ttl
     * @return void
     */
    public function Store($key, $value, $ttl = self::ONE_DAY)
    {
        apc_store($key, $value, $ttl);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return void
     */
    public function Add($key, $value, $ttl = self::ONE_DAY)
    {
        apc_add($key, $value, $ttl);
    }


    /**
     * @param mixed $key
     * @param string $default
     * @return mixed
     */
    public function Fetch($key, $default = '')
    {
        $res = apc_fetch($key, $success);
        return $success ? $res : $default;

    }

    /**
     * @param string $key
     * @return boolean
     */
    public function Delete($key)
    {
        return apc_delete($key);
    }

    /**
     * @return void
     */
    public function ClearCache()
    {
        apc_clear_cache('user');
    }
}
