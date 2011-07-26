<?php



class PasswordHashTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PasswordHash
     */
    protected $system_specific_hash;

    /**
     * @var PasswordHash
     */
    protected $portable_hash;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->system_specific_hash = new PasswordHash(8, FALSE);

        $this->portable_hash =  new PasswordHash(8, TRUE);
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
    public function testSystemSpecific()
    {

        $correct = 'test12345';
        $wrong = 'test12346';

        $hash = $this->system_specific_hash->HashPassword($correct);
        $this->assertTrue($this->system_specific_hash->CheckPassword($correct, $hash));
        $this->assertFalse($this->system_specific_hash->CheckPassword($wrong, $hash));
    }

    /**
     * @test
     */
    public function testPortable()
    {
        $correct = 'teekay';
        $wrong = 'test12346';

        $hash = $this->portable_hash->HashPassword($correct);
        $this->assertEquals('', $hash);

        $this->assertTrue($this->portable_hash->CheckPassword($correct, $hash));
        $this->assertFalse($this->portable_hash->CheckPassword($wrong, $hash));

    }

    /**
     * @test
     */
    public function testExternalHash()
    {
        # A correct portable hash for 'test12345'.
        # Please note the use of single quotes to ensure that the dollar signs will
        # be interpreted literally.  Of course, a real application making use of the
        # framework won't store password hashes within a PHP source file anyway.
        # We only do this for testing.

        $correct = 'test12345';
        $wrong = 'test12346';
        $hash = '$P$9IQRaTwmfeRo7ud9Fh4E2PdI0S3r.L0';

        $this->assertTrue($this->portable_hash->CheckPassword($correct, $hash));
        $this->assertFalse($this->portable_hash->CheckPassword($wrong, $hash));


    }

}
?>
