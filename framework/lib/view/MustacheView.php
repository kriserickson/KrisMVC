<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require_once 'Mustache.php';

/**
 * View Class that uses Moustache Templating engine instead of PHP.
 */
class MustacheView extends KrisView
{
    /**
     * @var Mustache|null
     */
    private $moustache = null;

    /**
     * @return Mustache
     */
    private function getMustache()
    {
        if ($this->moustache == null)
        {
            $this->moustache = new Mustache();
        }
        return $this->moustache;
    }

    /**
     * @param string $file
     * @param string|array $vars
     * @param bool $merge
     * @return string
     */
    public function fetch($file = '', $vars = array(), $merge = true)
    {
        if (strlen($file) == 0)
        {
            $file = $this->_file;
        }

        return $this->getMustache()->render(file_get_contents($file, true), $this->getVars($vars, $merge));

    }

    /**
     * @param string $template
     * @param array $vars
     * @param bool $merge
     * @return void
     */
    public function fetchFromString($template, $vars = array(), $merge = false)
    {
        $this->getMustache()->render($template, $this->getVars($vars, $merge));
    }


    /**
     * @param array|null $vars
     * @return void
     */
    public function dump($vars = null)
    {
        echo $this->contents($vars);
    }

    /**
     * @param array|null $vars
     * @return string
     */
    public function contents($vars = null)
    {
        return $this->getMustache()->render(file_get_contents($this->_file, true), $this->getVars($vars, $merge));
    }


}
