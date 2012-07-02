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
 * @package cache
 *
 * @property int Expiry
 * @property string Key
 * @property string Value
 */
class CacheDB extends KrisModel
{

    /**
     */
    function __construct()
    {
        parent::__construct('key', 'kris_cache_db');
        $this->initializeRecordSet(array('key', 'value', 'expiry'));

    }

    /**
     * Keeps a cache of the the databaseHandle..
     *
     * @return PDO
     */
    protected function getDatabaseHandle()
    {
        if (is_null($this->_dbh))
        {
            $this->_dbh = AutoLoader::Container()->get('CACHE_DB');
        }
        return $this->_dbh;
    }
}


/**
 * DBCache
 */
class DBCache extends Cache
{

    protected $_cacheDb;


    /**
     *
     */
    public function __construct()
    {
        $this->_cacheDb = new CacheDB();
    }


    /**
     * @param string $key
     * @param string $value
     * @param int $ttl
     * @return void
     */
    public function Store($key, $value, $ttl = self::ONE_DAY)
    {
        $this->_cacheDb->Key = $key;
        $this->_cacheDb->Expiry = time() + $ttl;
        $this->_cacheDb->Value = serialize($value);

        if ($this->_cacheDb->Exists())
        {
            $this->_cacheDb->Update();
        }
        else
        {
            $this->_cacheDb->Create();
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return void
     */
    public function Add($key, $value, $ttl = self::ONE_DAY)
    {
        $this->_cacheDb->Key = $key;

        if ($this->_cacheDb->Exists())
        {
            throw new Exception('Value already exists');
        }
        else
        {
            $this->_cacheDb->Expiry = time() + $ttl;
            $this->_cacheDb->Value = serialize($value);
            $this->_cacheDb->Create();
        }
    }

    /**
     * @param string $key
     * @param string $default
     * @return mixed
     */
    public function Fetch($key, $default = '')
    {
        $this->_cacheDb->Key = $key;

        if ($this->_cacheDb->Exists())
        {
            $this->_cacheDb->Retrieve($key);
            if ($this->_cacheDb->Expiry < time())
            {
                $this->Delete($key);
                return $default;
            }
            return unserialize($this->_cacheDb->Value);
        }
        else
        {
            return $default;
        }
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function Delete($key)
    {
        $this->_cacheDb->Key = $key;

        if ($this->_cacheDb->Exists())
        {
            $this->_cacheDb->Delete();
            return true;
        }
        return false;

    }

    /**
     * @return void
     */
    public function ClearCache()
    {
        $this->_cacheDb->Query('DELETE FROM '.$this->_cacheDb->TableName().' WHERE 1=1');
    }
}
