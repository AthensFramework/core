<?php

namespace Athens\Core\Form;

use Athens\Core\WritableBearer\WritableBearerBearerTrait;
use Athens\Core\Form\FormAction\FormAction;
use Athens\Core\Writable\WritableTrait;

trait FormTrait
{
    use WritableTrait;
    use WritableBearerBearerTrait;

    /** @var string */
    protected $method;

    /** @var string */
    protected $target;

    /** @var bool */
    protected $isValid;

    /** @var string[] */
    protected $errors = [];

    /** @var FormAction[] */
    protected $actions;

    /** @var callable */
    protected $onValidFunc;

    /** @var callable */
    protected $onInvalidFunc;

    /** @var array[]  */
    protected $validators;

    /** @return void */
    protected abstract function validate();

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @return mixed
     */
    public function onValid()
    {
        $args = array_merge([$this], func_get_args());
        return call_user_func_array($this->onValidFunc, $args);
    }

    /**
     * @return mixed
     */
    public function onInvalid()
    {
        $args = array_merge([$this], func_get_args());
        return call_user_func_array($this->onInvalidFunc, $args);
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        if ($this->isValid === null) {
            $this->validate();
        }
        return $this->isValid;
    }

    /**
     * @param string $error
     * @return void
     */
    public function addError($error)
    {
        $this->errors[] = $error;
    }

    /**
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return FormAction[]
     */
    public function getActions()
    {
        return $this->actions;
    }
}
