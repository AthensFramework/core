<?php

namespace UWDOEM\Framework\Form;

use UWDOEM\Framework\Etc\AbstractBuilder;
use UWDOEM\Framework\Field\FieldBuilder;
use UWDOEM\Framework\Field\Field;

/**
 * Class FormBuilder
 *
 * @package UWDOEM\Framework\Form
 */
class FormBuilder extends AbstractBuilder
{
    use FormBuilderTrait;

    /**
     * @param string $label
     * @return FormBuilder
     */
    public function addLabel($label)
    {
        $labelField = FieldBuilder::begin()
            ->setType(Field::FIELD_TYPE_SECTION_LABEL)
            ->setLabel($label)
            ->build();

        return $this->addFields([$label => $labelField]);
    }

    /**
     * @return Form
     * @throws \Exception If setFieldBearer has not been called.
     */
    public function build()
    {
        $this->validateId();

        $this->validateOnInvalidFunc();
        $this->validateOnValidFunc();
        $this->validateOnSuccessUrl();
        $this->validateActions();

        return new Form(
            $this->id,
            $this->type,
            $this->method,
            $this->target,
            $this->buildFieldBearer(),
            $this->onValidFunc,
            $this->onInvalidFunc,
            $this->actions,
            $this->subForms,
            $this->validators
        );
    }
}
