<?php

namespace Athens\Core\Admin;

use Exception;

use Propel\Runtime\ActiveQuery\ModelCriteria;

use Athens\Core\Etc\AbstractBuilder;
use Athens\Core\Writer\WritableInterface;
use Athens\Core\Section\SectionBuilder;
use Athens\Core\Etc\SafeString;
use Athens\Core\Page\PageBuilder;
use Athens\Core\Page\PageInterface;
use Athens\Core\Page\Page;

/**
 * Class PageBuilder
 *
 * @package Athens\Core\Page
 */
class AdminBuilder extends PageBuilder
{

    /** @var ModelCriteria[] */
    protected $queries = [];

    /** @var {PageInterface|null}[] */
    protected $detailPages = [];

    /**
     * @param WritableInterface $writable
     * @return PageBuilder
     */
    public function setWritable(WritableInterface $writable)
    {
        $this->writable = $writable;
        return $this;
    }

    /**
     * @param string[] $message
     * @return PageBuilder
     */
    public function setMessage(array $message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @param ModelCriteria     $objectManagerQuery
     * @param WritableInterface $detailPage
     * @return PageBuilder
     */
    public function addQuery(ModelCriteria $objectManagerQuery, WritableInterface $detailPage = null)
    {
        $this->queries[] = $objectManagerQuery;
        $this->detailPages[] = $detailPage;

        return $this;
    }

    /**
     * @return PageInterface
     * @throws Exception If the type of the page has not been set.
     */
    public function build()
    {
        $this->validateId();

        if ($this->queries === []) {
            throw new Exception(
                "For an object manager page, you must provide a Propel query(ies) using ::addQuery."
            );
        }

        $admin = new Admin(
            $this->id,
            $this->type,
            $this->classes,
            $this->title,
            $this->baseHref,
            $this->header,
            $this->subHeader,
            $this->breadCrumbs,
            $this->returnTo,
            $this->queries,
            $this->detailPages
        );

        return $admin;
    }
}
