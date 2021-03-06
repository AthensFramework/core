<?php

namespace Athens\Core\Table;

use Athens\Core\Filter\DummyFilter;
use Athens\Core\Row\RowInterface;
use Athens\Core\Filter\FilterInterface;
use Athens\Core\Writable\AbstractWritableBuilder;

/**
 * Class TableBuilder
 *
 * @package Athens\Core\Table
 */
class TableBuilder extends AbstractWritableBuilder
{

    /** @var RowInterface[] */
    protected $rows = [];

    /** @var FilterInterface */
    protected $filter;

    /**
     * @param RowInterface[] $rows
     * @return TableBuilder
     */
    public function addRows(array $rows)
    {
        $this->rows = array_merge($this->rows, $rows);
        return $this;
    }

    /**
     * @param RowInterface $row
     * @return TableBuilder
     */
    public function addRow(RowInterface $row)
    {
        return $this->addRows([$row]);
    }

    /**
     * @param FilterInterface $filter
     * @return TableBuilder
     */
    public function addFilter(FilterInterface $filter)
    {
        if ($this->filter !== null) {
            $filter->combine($this->filter);
        }
        $this->filter = $filter;
        return $this;
    }

    /**
     * @return Table
     */
    public function build()
    {
        $this->validateId();

        if ($this->filter === null) {
            $this->filter = new DummyFilter();
        }

        return new Table($this->id, $this->classes, $this->data, $this->rows, $this->filter);
    }
}
