<?php
/**
 *
 */
interface Writer
{

    /**
     * @return string
     */
    public function getExtensionWithoutDot();

    /**
     * @param string $receivedFilename
     * @return string
     */
    public function write($receivedFilename);
}
