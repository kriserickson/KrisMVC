<?php

/**
 *
 */
interface CronBase
{

    /**
     * @abstract
     * @return boolean
     */
    public function execute();

    /**
     * @abstract
     * @return string
     */
    public function getError();

    /**
     * @abstract
     * @return string
     */
    public function getMessage();

}