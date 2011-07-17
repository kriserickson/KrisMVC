<?php

/**
 * Used to pull arguments out of the command prompt
 * @package CodeGeneration
 */
class Args
{
    /**
     * @var array
     */
    private $_flags;

    private $_command;

    /**
     *
     */
    public function __construct()
    {
        $this->_flags = array();

        $argv = $GLOBALS['argv'];
        array_shift($argv);

        for ($i = 0; $i < count($argv); $i++)
        {
            $str = $argv[$i];

            // --foo
            if (strlen($str) > 2 && substr($str, 0, 2) == '--')
            {
                $str = substr($str, 2);
                $parts = explode('=', $str);
                $this->_flags[$parts[0]] = true;

                // Does not have an =, so choose the next arg as its value
                if (count($parts) == 1 && isset($argv[$i + 1]) && preg_match('/^--?.+/', $argv[$i + 1]) == 0)
                {
                    $this->_flags[$parts[0]] = $argv[$i + 1];
                }
                elseif (count($parts) == 2) // Has a =, so pick the second piece
                {
                    $this->_flags[$parts[0]] = $parts[1];
                }
            }
            elseif (strlen($str) == 2 && $str[0] == '-') // -a
            {
                $this->_flags[$str[1]] = true;
                if (isset($argv[$i + 1]) && preg_match('/^--?.+/', $argv[$i + 1]) == 0)
                {
                    $this->_flags[$str[1]] = $argv[++$i];
                }
            }
            elseif (strlen($str) > 1 && $str[0] == '-') // -abcdef
            {
                for ($j = 1; $j < strlen($str); $j++)
                {
                    $this->_flags[$str[$j]] = true;
                }
            }
            else if (strlen($str) > 1)
            {
                if (strlen($this->_command))
                {
                    throw new Exception('You can only have one command');
                }
                $this->_command = $str;
            }
        }

    }

    /**
     * Gets a flag or false if the flag doesn't exist...
     *
     * @param string|array $nameList
     * @param mixed $default
     * @return bool|string
     */
    public function flag($nameList, $default = false)
    {
        if (!is_array($nameList))
        {
            $nameList = array($nameList);
        }
        foreach ($nameList as $name)
        {
            if (isset($this->_flags[$name]))
            {
                return  $this->_flags[$name];
            }
        }
        return $default;
    }

    /**
     * Returns the command
     *
     * @return string
     */
    public function command()
    {
        return $this->_command;
    }
}
 
