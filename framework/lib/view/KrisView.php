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
 * @package View
 * 
 * View
 * For plain .php templates
 */
class KrisView
{
    protected $_file = '';
    protected $_vars = array('WEB_FOLDER' => KrisConfig::WEB_FOLDER);

    /**
     * @param string $file
     * @param null|array $vars
     */
    public function __construct($file = '', $vars = null)
    {
        if ($file)
        {
            $this->_file = $file;
        }
        if (is_array($vars))
        {
            $this->_vars = $vars;
        }
        return $this;
    }

    /**
     * @param string $key
     * @param string|int $var
     * @return KrisView
     */
    public function __set($key, $var)
    {
        return $this->set($key, $var);
    }

    /**
     * @param string $key
     * @param string|int $var
     * @return KrisView
     */
    public function set($key, $var)
    {
        $this->_vars[$key] = $var;
        return $this;
    }


    /**
     *  for adding to an array
     * @param string $key
     * @param string|int $var
     * @return KrisView
     */
    public function add($key, $var)
    {
        $this->_vars[$key] = $var;
        return $this;
    }

    /**
     * @param string $file
     * @param string|array $vars
     * @param bool $merge
     * @return string
     */
    public function fetch($file = '', $vars = array(), $merge = true)
    {
        extract($this->getVars($vars, $merge));

        if (strlen($file) == 0)
        {
            $file = $this->_file;
        }
        $fp = fopen($file, 'r', true);
        if (!$fp)
        {
            throw new Exception('Invalid template: '.$file);
        }
        ob_start();
        /** @noinspection PhpIncludeInspection */
        require($file);
        return ob_get_clean();
    }

    /**
     * @param array $vars
     * @param bool $merge
     * @return array
     */
    protected function getVars($vars, $merge)
    {
        if (is_null($vars))
        {
            return $this->_vars;
        }
        if ($merge)
        {
            return array_merge($this->_vars, $vars);
        }
        return $vars;
    }

    /**
     * @param array $vars
     * @param bool $merge
     */
    public function setVars($vars, $merge)
    {
        if ($merge)
        {
            $this->_vars = array_merge($this->_vars, $vars);
        }
        else
        {
            $this->_vars = $vars;
        }

    }


    /**
     * @param string $template
     * @param array $vars
     * @param bool $merge
     * @return string
     */
    public function fetchFromString($template, $vars = array(), $merge = false)
    {
        extract($this->getVars($vars, $merge));
        ob_start();
        eval('?>'.$template);
        return ob_get_clean();
    }

    /**
     * @param array|null $vars
     * @return void
     */
    public function dump($vars = null)
    {
        if (is_array($vars))
        {
            $this->_vars = array_merge($this->_vars, $vars);
        }
        extract($this->_vars);
        /** @noinspection PhpIncludeInspection */
        require($this->_file);
    }

    /**
     * @param array $vars
     * @param bool $merge
     * @return string
     */
    public function contents($vars = array(), $merge = false)
    {
        return $this->fetch($this->_file, $vars, $merge);
    }

}

 
