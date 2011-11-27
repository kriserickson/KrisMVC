<?php
require_once 'writers/Writer.php';
require_once 'writers/TextWriter.php';
require_once 'namers/Namer.php';
require_once 'namers/PHPUnitNamer.php';
require_once 'reporters/PHPUnitReporter.php';
require_once 'reporters/Reporter.php';


/**
 *
 */
class Approvals {

    /**
     * @var Reporter|null
     */
    private static $reporter = null;

    /**
     * @static
     * @param string $received
     */
    public static function approveString($received) {
		self::approve(new TextWriter($received, 'txt'), new PHPUnitNamer(), self::getReporter('txt'));
	}

    /**
     * @static
     * @param string $extensionWithoutDot
     * @return null|PHPUnitReporter
     */
    public static function getReporter($extensionWithoutDot) {
		if (is_null(self::$reporter)) {
		switch($extensionWithoutDot) {
			default:
				return new PHPUnitReporter();
			}
		}
		return self::$reporter;
	}

    /**
     * @static
     * @param Reporter $reporter
     */
    public static function useReporter(Reporter $reporter) {
		self::$reporter = $reporter;
	}

    /**
     * @static
     * @param Writer $writer
     * @param Namer $namer
     * @param Reporter $reporter
     * @throws RuntimeException
     */
    public static function approve(Writer $writer, Namer $namer, Reporter $reporter) {
		$extension = $writer->getExtensionWithoutDot();
		$approvedFilename = $namer->getApprovedFile($extension);
		$receivedFilename = $writer->write($namer->getReceivedFile($extension));
		if (!file_exists($approvedFilename)) {
			$approvedContents = null;
		} else {
			$approvedContents = file_get_contents($approvedFilename);
		}
		$receivedContents = file_get_contents($receivedFilename);
		if (self::equals($approvedContents,$receivedContents)) {
			unlink($receivedFilename);
		} else {
			$hint = "\n------ To Approve, use the following command ------\n";
			$hint .= 'mv -v "' . addslashes($receivedFilename) . '" "' . addslashes($approvedFilename) . "\"\n";
			$hint .= "\n\n";
			$reporter->reportFailure($approvedFilename, $receivedFilename); 
			throw new RuntimeException('Approval File Mismatch: ' . $receivedFilename . ' does not match ' . $approvedFilename . $hint);
		}
	}

    private static function equals($val1, $val2)
    {
        return self::standardize_line_endings($val1) == self::standardize_line_endings($val2);
    }

    /**
     * @static
     * @param array $list
     */
    public static function approveList(array $list) {
		$string = '';
		foreach($list as $key => $item) {
			$string .= '[' . $key . '] -> ' . $item . "\n";
		}
		self::approveString($string);
	}

    /**
     * @static
     * @param string $html
     */
    public static function approveHtml($html) {
		self::approve(new TextWriter($html, 'html'), new PHPUnitNamer(), self::getReporter('html'));
	}

    private static function standardize_line_endings($val)
    {
        return str_replace(array("\r\n", "\r"), array("\n", "\n"), $val);
    }


}