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
    protected  $verbosity = self::WARNING;

    /**
     * @var string
     */
    protected  $fatalMessage = 'An error occurred.';

    /**
     * @param int $verbosity
     */
    public function SetVerbosity($verbosity)
    {
        if ($verbosity > self::DEBUG)
        {
            $this->verbosity = self::DEBUG;
        }
        if ($verbosity < self::FATAL)
        {
            $this->verbosity = self::FATAL;
        }

    }

    /**
     * @param string $msg
     */
    public function SetFatalMessage($msg)
    {
        $this->fatalMessage = $msg;
    }

    /**
     * @return int
     */
    public function GetVerbosity()
    {
        return $this->verbosity;
    }

    /**
     * @abstract
     * @param $message
     * @param $level
     * @return mixed
     */
    protected abstract function doLog($message, $level);


    /**
     * @param string $msg
     */
    public function Debug($msg)
    {
        if ($this->verbosity >= self::DEBUG)
        {
            $this->doLog($msg, self::DEBUG);
        }
    }

    /**
     * @param string $msg
     */
    public function Warning($msg)
    {
        if ($this->verbosity >= self::WARNING)
        {
            $this->doLog($msg, self::WARNING);
        }
    }

    /**
     * @param string $msg
     */
    public function Error($msg)
    {
        if ($this->verbosity >= self::ERROR)
        {
            $this->doLog($msg, self::ERROR);
        }
    }

    /**
     * @param string $msg
     */
    public function Fatal($msg)
    {
        $this->doLog($msg, self::FATAL);
        die($this->fatalMessage);
    }

    /**
     * @param $level
     * @return string
     */
    protected  function getLevelString($level)
    {
        switch($level)
        {
            case self::FATAL: return 'Fatal';
            case self::DEBUG: return 'Debug';
            case self::WARNING: return 'Warning';
            case self::ERROR: default: return 'Error';
        }
    }


}

/**
 * Default log
 */
class KrisLog extends Log
{
    /**
     * @param $message
     * @param $level
     * @return void
     */
    protected function doLog($message, $level) {
        $levelString = $this->getLevelString($level);
        error_log($levelString.': '.$message);
    }
}

/**
 * Console log used to debug on the console...
 */
class ConsoleLog extends Log
{
    /**
     *
     */
    public function __construct()
    {
        $this->verbosity = self::DEBUG;
    }

    /**
     * @param $message
     * @param $level
     * @return void
     */
    protected function doLog($message, $level) {
        $levelString = $this->getLevelString($level);
        echo $levelString . ': ' . $message.PHP_EOL;
    }
}
