<?php

namespace UWDOEM\Framework\Form;

use UWDOEM\Framework\Form\FormAction\FormAction;
use UWDOEM\Framework\FieldBearer\FieldBearerBuilder;


class FormBuilder {

    /**
     * @var FormAction[]
     */
    protected $_actions = [];

    /**
     * @var callable
     */
    protected $_onValidFunc;

    /**
     * @var callable
     */
    protected $_onInvalidFunc;

    /**
     * @var FieldBearerBuilder
     */
    protected $_fieldBearerBuilder;

    /**
     * @var array[]
     */
    protected $_validators = [];

    protected function __construct() {
        $this->_fieldBearerBuilder = new FieldBearerBuilder();
    }

    /**
     * @param FormAction[] $actions
     * @return FormBuilder
     */
    public function setActions($actions) {
        $this->_actions = $actions;
        return $this;
    }

    /**
     * @param callable $onValidFunc
     * @return FormBuilder
     */
    public function setOnValidFunc($onValidFunc) {
        $this->_onValidFunc = $onValidFunc;
        return $this;
    }

    /**
     * @param callable $onInvalidFunc
     * @return FormBuilder
     */
    public function setOnInvalidFunc($onInvalidFunc) {
        $this->_onInvalidFunc = $onInvalidFunc;
        return $this;
    }

    /**
     * @param \Propel\Runtime\ActiveRecord\ActiveRecordInterface $object
     */
    public function addObject($object) {
        $this->_fieldBearerBuilder->addObject($object);
    }

    /**
     * @param string $fieldName
     * @param callable $callable
     */
    public function addValidator($fieldName, callable $callable) {
        if (!array_key_exists($fieldName, $this->_validators)) {
            $this->_validators[$fieldName] = [];
        }
        $this->_validators[$fieldName][] = $callable;

    }
    
    /**
     * @return FormBuilder
     */
    public static function begin() {
        return new static();
    }

    /**
     * @return FormBuilder
     */
    public function clear() {
        $this->_actions = null;
        $this->_onValidFunc = null;
        $this->_onInvalidFunc = null;
        $this->_fieldBearerBuilder = FieldBearerBuilder::begin();

        return $this;
    }

    /**
     * @return Form
     * @throws \Exception if setFieldBearer has not been called.
     */
    public function build() {
        if (!isset($this->_onInvalidFunc)) {

            $this->_onInvalidFunc = function (FormInterface $thisForm) {
                foreach ($thisForm->getFieldBearer()->getFields() as $field) {
                    if (array_key_exists($field->getSlug(), $_POST) && $field->getType() !== "literal") {
                        $field->setInitial($_POST[$field->getSlug()]);
                    }
                }
            };

        }

        if (!isset($this->_onValidFunc)) {
            $this->_onValidFunc = function() {};
        }

        return new Form(
            $this->_fieldBearerBuilder->build(),
            $this->_onValidFunc,
            $this->_onInvalidFunc,
            $this->_actions,
            $this->_validators
        );
    }
}