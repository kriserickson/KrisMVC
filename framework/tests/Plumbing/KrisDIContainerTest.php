<?php

/**
 * Test class for KrisDIContainer.
 * Generated by PHPUnit on 2011-08-01 at 11:58:46.
 */
class KrisDIContainerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var KrisDIContainer
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new KrisDIContainer;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }


    /**
     * @test
     */
    public function testGet()
    {
        $container = new KrisDIContainer();
        $container->registerImplementation('Zap', 'Foo');
        $container->registerImplementation('Cuux', 'Cuux');

        /** @var $cuux Cuux */
        $cuux = $container->get('Cuux');
        $cuux->setValue('test');

        /** @var $buux Cuux */
        $buux = $container->get('Cuux');

        // Make sure we have the same singleton
        $this->assertTrue($cuux === $buux);

        $this->assertTrue($buux->getValue() == 'test');

        // Make sure that changing one singleton changes the other...
        $buux->setValue('foogle');
        $this->assertTrue($cuux->getValue() == 'foogle');
    }

    /**
     * @test
     */
    public function testCreate()
    {
        $container = new KrisDIContainer();
        $container->registerImplementation('Zap', 'Foo');
        $container->registerImplementation('Cuux', 'Cuux');

        /** @var $cuux Cuux */
        $cuux = $container->create('Cuux');
        $cuux->setValue('test');

        // Create another distinct Cuux
        /** @var $buux Cuux */
        $buux = $container->create('Cuux');

        // Make sure they are distinct
        $this->assertFalse($cuux === $buux);
        $this->assertFalse($buux->getValue() == 'test');

        // Make sure that changing one Cuux does not change the other...
        $buux->setValue('foogle');
        $this->assertFalse($cuux->getValue() == 'foogle');
    }

    /**
     * @test
     */
    public function testCreateWithFactory()
    {
        // Create Foozle factory
        $container = new KrisDIContainer(array('Foozle' => function()
        {
            $cux = new Cuux(new Foo());
            $cux->setValue('Hello');
            return $cux;
        }));

        /** @var $cuux Cuux */
        $cuux = $container->create('Foozle');

        // Make sure the Foozle has hello preset
        $this->assertTrue($cuux->getValue() == 'Hello');

        /** @var $buux Cuux */
        $buux = $container->create('Foozle');

        // Make sure are Foozles are not the same...
        $this->assertFalse($cuux === $buux);

        // Make sure that changing one Foozle does not change the other...
        $buux->setValue('foogle');
        $this->assertFalse($cuux->getValue() == 'foogle');

    }


}

// Test classes

/**
 *
 */
interface Zap {}

/**
 *
 */
class Foo implements Zap {}


/**
 *
 */
class Cuux
{
    /**
     * @var string
     */
    private $_val;

    /**
     * @param Zap $zap
     */
    function __construct(Zap $zap) {}

    /**
     * @param string $val
     */
    function setValue($val) {
        $this->_val = $val;
    }

    /**
     * @return string
     */
    function getValue()
    {
        return $this->_val;
    }
}
