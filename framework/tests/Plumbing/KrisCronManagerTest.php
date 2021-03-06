<?php
        /**
         * Test class for KrisDIContainer.
         * Generated by PHPUnit on 2011-08-01 at 11:58:46.
         */
class KrisCronManagerTest extends PHPUnit_Framework_TestCase {

    private $cronLogger;

    public function setUp()
    {
        $cronLogger = $this->getMock('CronLogModel', array('Create', '__set', '__get'), array(), '', false);

        AutoLoader::Container()->registerFactory('CronLogger', function () use ($cronLogger) { return $cronLogger; });
        $this->cronLogger = $cronLogger;

    }

    /**
     * @test
     */
    public function testExecuteForced() {

        $cronModel = $this->getMock('CronModel', array('Retrieve'));

        $cronMock2 = $this->getMock('CronModel', array('Update'));
        $cronId = 1;
        $cronMock2->CronId = $cronId;
        $cronMock2->Name = 'TestCron';

        $cronModel->expects($this->once())->method('Retrieve')->with($cronId)->will($this->returnValue($cronMock2));
        $cronMock2->expects($this->once())->method('Update')->will($this->returnValue(true));
        $this->cronLogger->expects($this->once())->method('__get')->will($this->returnValue('Message'));

        $cronManager = new KrisCronManager($cronModel);

        $cronManager->manuallyExecuteTask($cronId);



    }

}
