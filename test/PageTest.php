<?php

namespace Athens\Core\Test;

use Athens\Core\Field\FieldInterface;
use Athens\Core\Link\Link;
use Athens\Core\Section\SectionInterface;
use PHPUnit_Framework_TestCase;

use Athens\Core\Writable\WritableInterface;
use Athens\Core\WritableBearer\WritableBearerInterface;
use Athens\Core\WritableBearer\WritableBearerBearerInterface;
use Athens\Core\Page\PageBuilder;
use Athens\Core\Page\Page;
use Athens\Core\Section\SectionBuilder;
use Athens\Core\Settings\Settings;

use Athens\Core\Test\Mock\MockQuery;
use Athens\Core\Test\Mock\MockHTMLWriter;
use Athens\Core\Test\Mock\MockInitializer;

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
     * @param WritableInterface $writable
     * @return WritableInterface[]
     */
    public function flattenWritables(WritableInterface $writable)
    {
        $writables = [$writable];

        if ($writable instanceof WritableBearerBearerInterface || $writable instanceof WritableBearerInterface) {
            if ($writable instanceof WritableBearerBearerInterface) {
                $writables[] = $writable->getWritableBearer();
            }

            foreach ($writable->getWritables() as $child) {
                $writables = array_merge($writables, $this->flattenWritables($child));
            }
        }

        return $writables;
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
        $baseHref = ".";
        $header = "header";
        $subHeader = "subHeader";
        $type = PageBuilder::TYPE_FULL_HEADER;

        $page = PageBuilder::begin()
            ->setId($id)
            ->setTitle($title)
            ->addClass($classes[0])
            ->addClass($classes[1])
            ->setBaseHref($baseHref)
            ->addBreadCrumb(array_keys($breadCrumbs)[0], array_values($breadCrumbs)[0])
            ->addWritable($writable)
            ->setHeader($header)
            ->setSubHeader($subHeader)
            ->setType($type)
            ->build();

        $writables = $this->flattenWritables($page->getWritableBearer());

        $this->assertEquals($id, $page->getId());
        $this->assertArraySubset($classes, $page->getClasses());
        $this->assertEquals($title, $page->getTitle());
        $this->assertContains($writable, $writables);
        $this->assertEquals($baseHref, $page->getBaseHref());
        $this->assertEquals($type, $page->getType());

        $breadCrumbFilter = function ($writable) use ($breadCrumbs) {
            return (
                $writable instanceof Link
                && $writable->getURI() === array_values($breadCrumbs)[0]
                && $writable->getText() === array_keys($breadCrumbs)[0]
            );
        };

        $headerFilter = function ($writable) use ($header) {
            return (
                $writable instanceof SectionInterface
                && $writable->getType() === SectionInterface::TYPE_HEADER
                && $writable->getWritables()[0] instanceof FieldInterface
                && strpos($writable->getWritables()[0]->getInitial(), $header) !== false
            );
        };

        $subheaderFilter = function ($writable) use ($subHeader) {
            return (
                $writable instanceof SectionInterface
                && $writable->getType() === SectionInterface::TYPE_SUBHEADER
                && $writable->getWritables()[0] instanceof FieldInterface
                && strpos($writable->getWritables()[0]->getInitial(), $subHeader) !== false
            );
        };

        $this->assertEquals(1, sizeof(array_filter($writables, $breadCrumbFilter)));
        $this->assertEquals(1, sizeof(array_filter($writables, $headerFilter)));
        $this->assertEquals(1, sizeof(array_filter($writables, $subheaderFilter)));
    }

    public function testRender()
    {
        $title = "Test Page";
        $page = PageBuilder::begin()
            ->setId("test-page")
            ->setType(PageBuilder::TYPE_FULL_HEADER)
            ->setTitle($title)
            ->build();

        ob_start();
        $page->render();
        $result = ob_get_clean();

        $this->assertContains("<title>$title</title>", $result);
    }
}
