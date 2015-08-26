<?php

namespace UWDOEM\Framework\Form;


use UWDOEM\Framework\FieldBearer\FieldBearerInterface;
use UWDOEM\Framework\Form\FormAction\FormAction;
use UWDOEM\Framework\Writer\WritableInterface;


interface FormInterface extends WritableInterface {

    /**
     * @return FieldBearerInterface
     */
    function getFieldBearer();

    /**
     * Maybe this should be protected??
     */
    function onValid();

    /**
     * Maybe this should be protected??
     */
    function onInvalid();

    /**
     * @return bool
     */
    function isValid();

    /**
     * @return string[]
     */
    function getErrors();

    /**
     * @return FormAction[]
     */
    function getActions();

}