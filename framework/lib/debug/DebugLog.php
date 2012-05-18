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
     */
    public function Debug($msg)
    {
        $this->_errorLog .= $this->AddToErrorLog($msg, 'Debug');
    }

    /**
     * @param string $msg
     */
    public function Warning($msg)
    {
        $this->_errorLog .= $this->AddToErrorLog($msg, 'Warning');
    }

    /**
     * @param string $msg
     */
    public function Error($msg)
    {
        $this->_errorLog .= $this->AddToErrorLog($msg, 'Error');
    }

    /**
     * @param string $msg
     * @throws Exception
     */
    public function Fatal($msg)
    {
        $this->_errorLog .= $this->AddToErrorLog($msg, 'Fatal');
        throw new Exception('Fatal Error: ' . $msg);
    }

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

}

