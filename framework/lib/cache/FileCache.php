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
 * DBCache
 */
class FileCache extends Cache
{

    private $_tmpDir;

    /**
     *
     */
    public function __construct()
    {
        $this->_tmpDir = sys_get_temp_dir();
    }


    /**
     * @param string $key
     * @param string $value
     * @param int $ttl
     * @return void
     */
    public function Store($key, $value, $ttl = self::ONE_DAY)
    {
        $file = $this->GetFilename($key);
        $expiry = time() + $ttl;
        file_put_contents($file, $expiry.PHP_EOL.$value);
    }

    /**
     * @param string $key
     * @param string $value
     * @param int $ttl
     * @return void
     */
    public function Add($key, $value, $ttl = self::ONE_DAY)
    {
        $file = $this->GetFilename($key);
        $expiry = time() + $ttl;
        if (!file_exists($file))
        {
            file_put_contents($file, $expiry.PHP_EOL.$value);
        }
        else
        {
            throw new Exception('Value already exists');
        }

    }

    /**
     * @param string $key
     * @param string $default
     * @return string
     */
    public function Fetch($key, $default = '')
    {
        $file = $this->GetFilename($key);
        if(($fp = fopen($file, 'r')) === false)
        {
            return false;
        }
        $expires = (int)fgets($fp);
        if($expires > time())
        {
            $str = '';
            while(($line = fgets($fp)) !== false)
            {
                $str .= $line;
            }
            fclose($fp);
            return $str;
        }
        fclose($fp);
        unlink($file);
        return false;
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function Delete($key)
    {
        $file = $this->GetFilename($key);
        if (file_exists($file))
        {
            unlink($file);
            return true;
        }
        return false;
    }

    /**
     * @return boolean
     */
    public function ClearCache()
    {
        $deleted = false;
        $files = glob($this->_tmpDir . '*.cache');
        foreach($files as $file)
        {
            unlink($file);
            $deleted = true;
        }

        return $deleted;
    }

    /**
     * @param string $key
     * @return string
     */
    private function GetFilename($key)
    {
        return $this->_tmpDir . md5($key) . '.cache';
    }
}
