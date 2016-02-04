<?php

namespace UWDOEM\Framework\Test;

use PHPUnit_Framework_TestCase;

use UWDOEM\Framework\Page\PageBuilder;
use UWDOEM\Framework\Page\Page;
use UWDOEM\Framework\Section\SectionBuilder;
use UWDOEM\Framework\Etc\Settings;

use UWDOEM\Framework\Test\Mock\MockQuery;
use UWDOEM\Framework\Test\Mock\MockWriter;
use UWDOEM\Framework\Test\Mock\MockInitializer;

class PageTest extends PHPUnit_Framework_TestCase
{

    /**
     * @return PageBuilder[]
     */
    public function testedSectionBuilders()
    {
        // Return a fieldBearerBuilder of every type you want to test
        return [
            PageBuilder::begin(),
        ];
    }

    /**
     * Basic tests for the Section builder classes.
     *
     * Any test here could potentially fail because of a failure in the constructed section.
     *
     * @throws \Exception
     */
    public function testBuilder()
    {

        $content = "content";
        $label = "label";

        $writable = SectionBuilder::begin()
            ->setId("s" . (string)rand())
            ->addContent($content)
            ->addLabel($label)
            ->build();

        $id = "i" . (string)rand();
        $title = "title";
        $classes = [(string)rand(), (string)rand()];
        $breadCrumbs = ["name" => "http://link"];
        $returnTo = ["Another name" => "http://another.link"];
        $baseHref = ".";
        $header = "header";
        $subHeader = "subHeader";
        $type = Page::PAGE_TYPE_FULL_HEADER;

        $page = PageBuilder::begin()
            ->setId($id)
            ->setTitle($title)
            ->addClass($classes[0])
            ->addClass($classes[1])
            ->setBaseHref($baseHref)
            ->setBreadCrumbs($breadCrumbs)
            ->setWritable($writable)
            ->setHeader($header)
            ->setSubHeader($subHeader)
            ->setReturnTo($returnTo)
            ->setType($type)
            ->build();

        $this->assertEquals($id, $page->getId());
        $this->assertEquals($classes, $page->getClasses());
        $this->assertEquals($title, $page->getTitle());
        $this->assertEquals($writable, $page->getWritable());
        $this->assertEquals($baseHref, $page->getBaseHref());
        $this->assertEquals($breadCrumbs, $page->getBreadCrumbs());
        $this->assertEquals($header, $page->getHeader());
        $this->assertEquals($subHeader, $page->getSubHeader());
        $this->assertEquals($returnTo, $page->getReturnTo());
        $this->assertEquals($type, $page->getType());
    }

    /**
     * @expectedException              \Exception
     * @expectedExceptionMessageRegExp #Must use ::setId to provide a unique id.*#
     */
    public function testBuildFilterErrorWithoutHandle()
    {
        $filter = PageBuilder::begin()
            ->setType(Page::PAGE_TYPE_FULL_HEADER)
            ->build();
    }

    public function testRender()
    {
        /* No writer provided to render, page uses default writer class from settings */

        // Store the current default writer/initializer from the settings
        $defaultWriterClass = Settings::getDefaultWriterClass();
        $defaultInitializerClass = Settings::getDefaultInitializerClass();

        Settings::setDefaultWriterClass("\\UWDOEM\\Framework\\Test\\Mock\\MockWriter");
        Settings::setDefaultInitializerClass("\\UWDOEM\\Framework\\Test\\Mock\\MockInitializer");

        $title = "Test Page";
        $page = PageBuilder::begin()
            ->setId("test-page")
            ->setType(PAGE::PAGE_TYPE_FULL_HEADER)
            ->setTitle($title)
            ->setWritable(SectionBuilder::begin()->setId("s" . (string)rand())->addContent("content")->build())
            ->build();

        // Our mock writer will simply echo the title of the page
        $page->render(null, null);

        $this->assertTrue(MockInitializer::$used);
        $this->assertTrue(MockWriter::$used);

        // Set $used back to false on the initializer and writer
        MockInitializer::$used = false;
        MockWriter::$used = false;

        // Return the default writer/initializer class to its original value
        Settings::setDefaultWriterClass($defaultWriterClass);
        Settings::setDefaultInitializerClass($defaultInitializerClass);

        /* Writer provided to render */
        $page = PageBuilder::begin()
            ->setId("test-page")
            ->setType(PAGE::PAGE_TYPE_FULL_HEADER)
            ->setWritable(SectionBuilder::begin()->setId("s" . (string)rand())->addContent("content")->build())
            ->build();

        $writer = new MockWriter();
        $initializer = new MockInitializer();

        // Our mock writer will simply echo the title of the page
        $page->render($initializer, $writer);

        $this->assertTrue(MockInitializer::$used);
        $this->assertTrue(MockWriter::$used);

    }

    public function testBuildAjaxActionPage()
    {
        $status = (string)rand();
        $messageContent = (string)rand();

        $message = [
            "status" => $status,
            "message" => $messageContent
        ];

        $requestURI = (string)rand();

        $_SERVER["REQUEST_URI"] = $requestURI;

        $page = PageBuilder::begin()
            ->setId("test-page")
            ->setType(PAGE::PAGE_TYPE_AJAX_ACTION)
            ->setMessage($message)
            ->build();

        $_SERVER["REQUEST_URI"] = null;

        // Assert that the page contains a section, with content equal to the json
        // encoding of message.
        $this->assertEquals(json_encode($message), $page->getWritable()->getWritables()[0]->getInitial());
        $this->assertContains($requestURI, $page->getWritable()->getId());
    }

    /**
     * @expectedException              Exception
     * @expectedExceptionMessageRegExp #For an object manager page, you must provide a Propel query.*#
     */
    public function testBuildObjectManagerPageWithoutQuery()
    {
        $page = PageBuilder::begin()
            ->setId("test-page")
            ->setType(Page::PAGE_TYPE_OBJECT_MANAGER)
            ->build();
    }

    /**
     * @expectedException              Exception
     * @expectedExceptionMessageRegExp #You may only provide an object manager query for object manager pages.*#
     */
    public function testBuildNonObjectManagerPageWithQuery()
    {
        $query = new MockQuery();
        $page = PageBuilder::begin()
            ->setId("test-page")
            ->setType(Page::PAGE_TYPE_FULL_HEADER)
            ->setObjectManagerQuery($query)
            ->build();
    }

    /**
     * @expectedException              Exception
     * @expectedExceptionMessageRegExp #You must provide a message.*#
     */
    public function testBuildAjaxActionPageWithoutMessageRaisesException()
    {
        $page = PageBuilder::begin()
            ->setId("test-page")
            ->setType(PAGE::PAGE_TYPE_AJAX_ACTION)
            ->build();
    }

    /**
     * @expectedException              Exception
     * @expectedExceptionMessageRegExp #You may only set a message on an ajax-action page.*#
     */
    public function testBuildNonAjaxActionPageWithMessageRaisesException()
    {
        $page = PageBuilder::begin()
            ->setId("test-page")
            ->setType(PAGE::PAGE_TYPE_MULTI_PANEL)
            ->setMessage(["test", "messages"])
            ->build();
    }

    /*
     * The below methods are tested sufficiently above
    public function testGetWritables() {

    }

    public function testGetLabel() {

    }

    public function testGetContent() {

    }
    */
}
