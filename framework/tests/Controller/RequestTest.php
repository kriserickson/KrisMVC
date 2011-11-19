<?php



/**
 * Test class for Request.
 * Generated by PHPUnit on 2011-11-06 at 20:24:30.
 */
class RequestTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Request
     */
    protected $request;

    private $params = array('test1');
    private $controller = 'main';
    private $action = 'index?foo=bar';


    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $_POST['email'] = 'test@example.com';
        $this->request = new Request($this->controller, $this->action, $this->params);
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
    public function testParams()
    {
        $this->assertEquals($this->params, $this->request->Params());
    }

    /**
     * @test
     */
    public function testController()
    {
        $this->assertEquals($this->controller, $this->request->Controller());
    }

    /**
     * @test
     */
    public function testAction()
    {
        $this->assertEquals($this->action, $this->request->Action());
    }

    /**
     * @test
     */
    public function testParam()
    {
        $this->assertEquals($this->params[0], $this->request->Param(0));
    }


    /**
     * @test
     */
    public function testPostVar()
    {
        $this->assertEquals('test@example.com', $this->request->PostVar('email'));
    }

    /**
     * @test
     */
    public function testIsPosted()
    {
        $this->assertTrue($this->request->IsPosted('email'));
        $this->assertFalse($this->request->IsPosted('password'));
    }

    /**
     * @test
     */
    public function testGetVar()
    {
        $_SERVER['QUERY_STRING'] = 'foo=bar';
        $this->request = new Request($this->controller, $this->action, array());
        $this->assertEquals('bar', $this->request->GetVar('foo'));
    }

    /**
     * @test
     */
    public function testHasGet()
    {
        $_SERVER['QUERY_STRING'] = 'foo=bar';
        $this->request = new Request($this->controller, $this->action, array());
        $this->assertTrue($this->request->HasGet('foo'));
        $this->assertFalse($this->request->HasGet('bar'));
    }


}

?>
