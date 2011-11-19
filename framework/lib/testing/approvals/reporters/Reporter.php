<?php
interface Reporter
{
    /**
    * @param string $approvedFilename
    * @param string $receivedFilename
    */
	public function reportFailure($approvedFilename, $receivedFilename);
}