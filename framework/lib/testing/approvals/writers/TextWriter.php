<?php
require_once 'Writer.php';

/**
 *
 */
class TextWriter implements Writer {
    /**
     * @var string
     */
    private $received;

    /**
     * @var string
     */
    private $extensionWithoutDot;

    /**
     * @param string $received
     * @param string $extensionWithoutDot
     */
    public function __construct($received, $extensionWithoutDot) {
		$this->received = $received;
		$this->extensionWithoutDot = $extensionWithoutDot;
	}

    /**
     * @return string
     */
    public function getExtensionWithoutDot() {
		return $this->extensionWithoutDot;
	}

    /**
     * @param string $receivedFilename
     * @return string
     */
    public function write($receivedFilename) {
		file_put_contents($receivedFilename, $this->received);
		return $receivedFilename;
	}
}