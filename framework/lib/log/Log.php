<?php

/**
 * @package log
 */
abstract class Log
{
    const FATAL = 1;
    const ERROR = 2;
    const WARNING = 3;
    const DEBUG = 4;

    /**
     * @var int
     */
    protected  $_verbosity = self::WARNING;

    /**
     * @var string
     */
    protected  $_fatalMessage = 'An error occurred.';

    /**
     * @param int $verbosity
     */
    public function SetVerbosity($verbosity)
    {
        if ($verbosity > self::DEBUG)
        {
            $this->_verbosity = self::DEBUG;
        }
        if ($verbosity < self::FATAL)
        {
            $this->_verbosity = self::FATAL;
        }

    }

    /**
     * @param string $msg
     */
    public function SetFatalMessage($msg)
    {
        $this->_fatalMessage = $msg;
    }

    /**
     * @return int
     */
    public function GetVerbosity()
    {
        return $this->_verbosity;
    }

    /**
     * @param string $msg
     */
    public function Debug($msg)
    {
        if ($this->_verbosity >= self::DEBUG)
        {
            error_log($msg);
        }
    }

    /**
     * @param string $msg
     */
    public function Warning($msg)
    {
        if ($this->_verbosity >= self::WARNING)
        {
            error_log($msg);
        }
    }

    /**
     * @param string $msg
     */
    public function Error($msg)
    {
        if ($this->_verbosity >= self::ERROR)
        {
            error_log($msg);
        }
    }

    /**
     * @param string $msg
     */
    public function Fatal($msg)
    {
        error_log($msg);
        die($this->_fatalMessage);
    }

}

/**
 *
 */
class KrisLog extends Log
{


}