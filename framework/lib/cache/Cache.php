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
 * @package cache
 */
abstract class Cache
{

    const ONE_DAY = 86400;

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
     * @return boolean
     */
    abstract public function ClearCache();

}

