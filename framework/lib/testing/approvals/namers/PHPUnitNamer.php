<?php
/**
 *
 */
class PHPUnitNamer implements Namer {
    /**
     * @var string|null
     */
    private $caller;
    /**
     * @var string|null
     */
    private $testDirectory;

    /**
     * Constarctor
     */
    public function __construct() {
		$stackTraceLines = debug_backtrace(false);
		$this->caller = null;
		$this->testDirectory = null;
		foreach($stackTraceLines as $stackTraceLine) {
			if (array_key_exists('file', $stackTraceLine)) {
				if (self::isPHPUnitTest($stackTraceLine['file'])) {
					break;
				}
				$this->testDirectory = dirname($stackTraceLine['file']);
			}
			$this->caller = $stackTraceLine;
		}
	}

    /**
     * @static
     * @param string $path
     * @return bool
     */
    public static function isPHPUnitTest($path) {
		$expectedPath = DIRECTORY_SEPARATOR . 'PHPUnit' . DIRECTORY_SEPARATOR . 'Framework' . DIRECTORY_SEPARATOR . 'TestCase.php';
		$pathPart = substr($path, -strlen($expectedPath));
		return $pathPart === $expectedPath;
	}

    /**
     * @param string $extensionWithoutDot
     * @return string
     */
    public function getApprovedFile($extensionWithoutDot) {
		return $this->getFile('approved', $extensionWithoutDot);
	}

    /**
     * @param string $extensionWithoutDot
     * @return string
     */
    public function getReceivedFile($extensionWithoutDot) {
		return $this->getFile('received', $extensionWithoutDot);
	}

    /**
     * @param string $status
     * @param string $extensionWithoutDot
     * @return string
     */
    private function getFile($status, $extensionWithoutDot) {
		return $this->getCallingTestDirectory() . DIRECTORY_SEPARATOR . $this->getCallingTestClassName() . '.' . $this->getCallingTestMethodName() . '.' . $status . '.' . $extensionWithoutDot; 
	}

    /**
     * @return string
     */
    public function getCallingTestClassName() {
		return $this->caller['class'];
	}

    /**
     * @return string
     */
    public function getCallingTestMethodName() {
		return $this->caller['function'];
	}

    /**
     * @return string
     */
    public function getCallingTestDirectory() {
		return $this->testDirectory;
	}
}