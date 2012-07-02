<?php

/**
 * @package debug
 */
class DebugLog extends Log
{
    /**
     * @var string
     */
    protected $_errorLog;

    /**
     * @param string $msg
     * @param string $type
     * @return string
     */
    protected function AddToErrorLog($msg, $type)
    {
        return '['.date('y-m-d h:i:s:u').'] Message '.$type.': '.$msg.PHP_EOL;
    }

    /**
     * @return string
     */
    public function GetErrorLog()
    {
        return $this->_errorLog;
    }

    /**
     * @param string $message
     * @param int $level
     * @throws Exception
     */
    protected function doLog($message, $level) {
        $this->_errorLog .= $this->AddToErrorLog($message, $this->getLevelString($level));
        if ($level == self::FATAL)
        {
            throw new Exception('Fatal Error: ' . $message);
        }
    }
}

