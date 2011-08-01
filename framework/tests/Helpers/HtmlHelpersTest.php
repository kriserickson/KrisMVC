<?php



/**
 * Test class for HtmlHelpers.
 * Generated by PHPUnit on 2011-08-01 at 12:11:53.
 */
class HtmlHelpersTest extends PHPUnit_Framework_TestCase
{

    /**
     * @todo Implement testCreateButton().
     */
    public function testCreateButton()
    {
        $this->assertEquals('<button id="test" name="test" class="buttonClass">Button</button>', HtmlHelpers::CreateButton('test', 'Button', 'buttonClass'));

        $this->assertEquals('<button id="test" name="testName" class="buttonClass">Button</button>',
            HtmlHelpers::CreateButton('test', 'Button', 'buttonClass', 'testName'));

        $this->assertEquals('<button id="test" name="testName" class="buttonClass" style="buttonStyle">Button</button>',
            HtmlHelpers::CreateButton('test', 'Button', 'buttonClass', 'testName', 'buttonStyle'));

        $this->assertEquals('<button id="test" name="test" class="buttonClass" style="buttonStyle">Button</button>',
            HtmlHelpers::CreateButton('test', 'Button', 'buttonClass', '', 'buttonStyle'));
    }

    /**
     * @test
     */
    public function testCreateUnorderedListItem()
    {
        $this->assertEquals('<li id="listItem" class="listItemClass">OneItem</li>', HtmlHelpers::CreateUnorderedListItem('listItem', 'listItemClass', 'OneItem'));

        $this->assertEquals('<li id="listItem" class="listItemClass" style="listItemStyle">OneItem</li>', HtmlHelpers::CreateUnorderedListItem('listItem', 'listItemClass', 'OneItem', 'listItemStyle'));
    }

    /**
     * @test
     */
    public function testCreateLink()
    {
        $this->assertEquals('<a id="linkId" class="linkClass" href="http://example.com">Example Dot Com</a>', HtmlHelpers::CreateLink('linkId', 'linkClass', 'http://example.com', 'Example Dot Com'));
        $this->assertEquals('<a id="linkId" class="linkClass" style="linkStyle" href="http://example.com">Example Dot Com</a>', HtmlHelpers::CreateLink('linkId', 'linkClass', 'http://example.com', 'Example Dot Com', 'linkStyle'));
    }

//    /**
//     * @todo Implement testCreateSelect().
//     */
//    public function testCreateSelect()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//
//    /**
//     * @todo Implement testCreateInput().
//     */
//    public function testCreateInput()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//
//    /**
//     * @todo Implement testCreateTextarea().
//     */
//    public function testCreateTextarea()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//
//    /**
//     * @todo Implement testCreateScript().
//     */
//    public function testCreateScript()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//
//    /**
//     * @todo Implement testCreateImage().
//     */
//    public function testCreateImage()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//
//    /**
//     * @todo Implement testCreateFileInput().
//     */
//    public function testCreateFileInput()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
}
?>
