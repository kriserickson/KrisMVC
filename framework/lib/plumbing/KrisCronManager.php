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
 */
class KrisCronManager {

    /**
     * @var CronModel
     */
    private $cron;

    /**
     * @var Log
     */
    private $log;

    /**
     * @var string
     */
    private $error;

    const SECONDS_IN_MINUTE = 60;
    const SECONDS_IN_HOUR = 3600;
    const SECONDS_IN_DAY = 86400;
    public $message;

    /**
     *
     */
    function __construct($cronModel = null) {
        $this->cron = is_null($cronModel) ? new CronModel() : $cronModel;
        $this->log = AutoLoader::Container()->get('Log');
    }

    /*
    * if not, proceed to tick() and execute a normal cron tick.
    */
    /**
     *
     */
    public function go() {
        if (($_SERVER['argc'] > 1)) {
            for ($i = 1; $i < $_SERVER['argc']; $i++) {
                $this->manuallyExecuteTask($_SERVER['argv'][$i]);
            }
        } else {
            $this->tick();
        }
    }

    /**
     * @return mixed
     */
    function getError() {
        return $this->error;
    }

    /**
     *
     */
    private function tick() {

        $cronTaskList = $this->cron->RetrieveMultiple();
        $this->log->Debug('Retreived '.count($cronTaskList).' tasks');
        foreach ($cronTaskList as $cronTask) {
            if (!$cronTask->IsEnabled) {
                $this->log->Debug('Task '.$cronTask->Name.' is not enabled');
                continue;
            }
            $period = substr($cronTask->Frequency, 0, 1);
            $offset = (int)substr($cronTask->Frequency, 1);
            $performAction = false;

            // check the frequency field to see if we should do the current event.
            switch ($period) {
                case 'W': // daily task
                    if ($this->shouldExecuteTask($cronTask->LastRunTimestamp(), self::SECONDS_IN_DAY * 7, $offset * self::SECONDS_IN_DAY)) {
                        $this->executeAndLogTask($cronTask);
                        $performAction = true;
                    }
                    break;

                case 'D': // daily task
                    if ($this->shouldExecuteTask($cronTask->LastRunTimestamp(), self::SECONDS_IN_DAY, $offset * self::SECONDS_IN_HOUR) && $this->checkHour($offset)) {
                        $this->executeAndLogTask($cronTask);
                        $performAction = true;
                    }
                    break;

                case 'H': // hourly task
                    if ($this->shouldExecuteTask($cronTask->LastRunTimestamp(), self::SECONDS_IN_HOUR, $offset * self::SECONDS_IN_MINUTE)) {
                        $this->executeAndLogTask($cronTask);
                        $performAction = true;
                    }
                    break;
                case 'M': // minute task
                    if ($this->shouldExecuteTask($cronTask->LastRunTimestamp(), self::SECONDS_IN_MINUTE * $offset, $offset)) {
                        $this->executeAndLogTask($cronTask);
                        $performAction = true;
                    }
                    break;
                case '*': // run task as often as possible
                    $this->executeAndLogTask($cronTask);
                    $performAction = true;
                    break;
            }

            if (!$performAction)
            {
                $this->log->Debug('Task ' . $cronTask->Name . ' was not triggered.  Period: '. $period.' Last Run: '.$cronTask->LastRun);
            }
        }
    }

    /**
     * @param $offset
     * @return bool
     */
    private function checkHour($offset) {
        // additionally push offset to appropriate time zone.
        $midnight = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
        //$oneAm = $midnight + self::SECONDS_IN_HOUR;
        $currentTime = time();

        return $currentTime > ($midnight + $offset); // && $currentTime < ($oneAm + $offset);
    }


    /**
     * @param $lastCron
     * @param $secondsTolerance
     * @param int $offset
     * @return bool
     */
    private function shouldExecuteTask($lastCron, $secondsTolerance, $offset = 0) {

        if (($offset > $secondsTolerance) || ($offset < 0)) // make sure the offset makes sense.
        {
            $offset = 0;
        }

        return (((int)((time() - $offset) / $secondsTolerance)) > ((int)(($lastCron - $offset) / $secondsTolerance)));
    }

    /**
     * @param $cronId
     * @return bool
     */
    public function manuallyExecuteTask($cronId) {

        $task = $this->cron->Retrieve($cronId);

        if (!$task) {
            $this->error = 'Could not load task with id: "' . $cronId . '"';
            return false;
        } else {

            return $this->executeAndLogTask($task);
        }
    }

    /**
     * @param $cronTask CronModel
     * @return bool
     */
    private function executeAndLogTask($cronTask) {

        // Get the time before running the task, and make sure the time in the cron table matches that in the cron_log table..
        $dateTime = date(KrisDB::ISO_DATE_STRING);
        $res = $this->executeTask($cronTask, $dateTime);

        /** @var $cronLog CronLogModel  */
        $cronLog = AutoLoader::Container()->create('CronLogger');
        $cronLog->CronId = $cronTask->CronId;
        $cronLog->ExecutionDate = $dateTime;
        $cronLog->Success = $res;
        $cronLog->Message = $res ? $this->message : $this->error;

        $this->log->Debug('Task ' . ($res ? 'succeeded' : 'failed') . ' '.$cronLog->Message);

        $cronLog->Create();

        return $res;
    }

    /**
     * @param $cronTask CronModel
     * @param $dateTime string (date)
     * @return bool
     */
    private function executeTask($cronTask, $dateTime) {
        $res = false;
        if ($this->isLocked($cronTask)) {
            $this->error = 'Task locked';
        } else {
            $cronTask->Pid = ($pid = getmypid()) ? $pid : -1;
            if (!$cronTask->Update()) {
                $this->error = 'Failed to update Pid';

            } else if ($this->performCron($cronTask)) {
                $cronTask->LastRun = date($dateTime, time()); // update the last_cron field.
                $cronTask->Pid = 0; // clear the lock.
                $cronTask->Update();
                $res = true;
            }
        }
        return $res;
    }

    /**
     * @param $cronTask CronModel
     * @return bool
     */
    private function isLocked($cronTask) {
        if ($cronTask->Pid) {
            if ($cronTask->Pid == -1) // cron is running and we don't have a pid.
            {
                return true;
            }

            // check that the pid is actually still running.
            if ($this->processIsRunning((int)$cronTask->Pid)) {
                return true; // the proc which locked is still running.  lock is valid.
            }

            // lock is not valid.  treat as unlocked.
        }
        return false;
    }

    /**
     * @param int $pid
     * @return bool
     */
    private function processIsRunning($pid) {
        if (function_exists('posix_kill')) {
            // sending a zero will not actually kill anything.
            $procIsRunning = posix_kill($pid, 0);

        } else {
            /** @noinspection PhpUndefinedClassInspection */
            $wmi = new COM("winmgmts:{impersonationLevel=impersonate}!\\\\.\\root\\cimv2");
            /** @noinspection PhpUndefinedMethodInspection */
            $processes = $wmi->ExecQuery("SELECT * FROM Win32_Process WHERE ProcessId='" . $pid . "'");
            $processCount = 0;
            foreach ($processes as $junk) {
                $processCount++;
            }
            $procIsRunning = $processCount > 0;
        }
        return $procIsRunning;
    }

    /**
     * @param $cronName
     * @return string
     */
    private function getCronClassName($cronName)
    {
         return 'Cron'.str_replace(' ', '', ucwords(str_replace('_', ' ', $cronName)));
    }

    /**
     * @param $cronTask CronModel
     * @return bool
     */
    private function performCron($cronTask) {
        $cronClass = $this->getCronClassName($cronTask->Name);

        $this->error = '';

        if (class_exists($cronClass, true))
        {
            /** @var $task CronBase */
            $task = new $cronClass();
            if (!$task->execute())
            {
                $this->error = $task->getError();
            }
            else
            {
                $this->message = $task->getMessage();
            }

        }
        else
        {
            $this->error = 'Class '.$cronClass.' does not exist for cron task '.$cronTask->Name;
        }

        return strlen($this->error) == 0;
    }

    /**
     * @return mixed
     */
    public function getMessage() {
        return $this->message;
    }


}