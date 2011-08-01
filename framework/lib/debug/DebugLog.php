<?php

class DebugLog extends Log
{
    protected $_errorLog;

    public function Debug($msg)
    {
        $this->_errorLog .= $this->AddToErrorLog($msg, 'Debug');
    }
    public function Warning($msg)
    {
        $this->_errorLog .= $this->AddToErrorLog($msg, 'Warning');
    }

    public function Error($msg)
    {
        $this->_errorLog .= $this->AddToErrorLog($msg, 'Error');
    }

    public function Fatal($msg)
    {
        $this->_errorLog .= $this->AddToErrorLog($msg, 'Fatal');
        throw new Exception('Fatal Error: ' . $msg);
    }

    protected function AddToErrorLog($msg, $type)
    {
        return '['.date('y-m-d h:i:s:u').'] Message '.$type.': '.$msg.PHP_EOL;
    }

    public function GetErrorLog()
    {
        return $this->_errorLog;
    }

}

