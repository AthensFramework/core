<?php

namespace UWDOEM\Framework\FieldBearer;

use UWDOEM\Framework\Visitor\VisitableTrait;

class FieldBearer implements FieldBearerInterface
{

    /**
     * @var FieldBearerInterface[]
     */
    protected $fieldBearers = [];

    /**
     * @var \UWDOEM\Framework\Field\FieldInterface[]
     */
    protected $fields = [];

    /**
     * @var String[]
     */
    protected $visibleFieldNames;

    /**
     * @var String[]
     */
    protected $hiddenFieldNames = [];

    /**
     * @var callable
     */
    protected $saveFunction;

    use VisitableTrait;

    public function __construct($fields, $fieldBearers, $visibleFieldNames, $hiddenFieldNames, $saveFunction)
    {
        $this->fields = $fields ? $fields : [];
        $this->fieldBearers = $fieldBearers ? $fieldBearers : [];
        $this->visibleFieldNames = $visibleFieldNames ? $visibleFieldNames : null;
        $this->hiddenFieldNames = $hiddenFieldNames ? $hiddenFieldNames : null;

        if (is_callable($saveFunction)) {
            $this->saveFunction = $saveFunction;
        }
    }

    /**
     * @param string $fieldGetterFunction
     * @param \UWDOEM\Framework\Field\FieldInterface[] $initial
     * @return \UWDOEM\Framework\Field\FieldInterface[]
     */
    public function getFieldsBase($fieldGetterFunction, $initial)
    {
        foreach ($this->fieldBearers as $name => $fieldBearer) {

            $fields = $fieldBearer->$fieldGetterFunction();

            $prefixedFieldNames = [];
            foreach (array_keys($fields) as $key => $name) {
                while (array_key_exists($name, $initial) || in_array($name, $prefixedFieldNames, true)) {
                    $name = "_" . $name;
                }
                $prefixedFieldNames[$key] = $name;
            }
            $fields = array_combine($prefixedFieldNames, $fields);
            $initial = array_merge($initial, $fields);
        }
        return $initial;
    }

    /**
     * Return the array of child fields.
     * @return \UWDOEM\Framework\Field\FieldInterface[]
     */
    public function getFields()
    {
        $base = $this->fields;
        return $this->getFieldsBase("getFields", $base);
    }

    public function getFieldNames()
    {
        return array_keys($this->getFields());
    }

    public function getVisibleFieldNames()
    {
        return array_keys($this->getVisibleFields());
    }

    public function getHiddenFieldNames()
    {
        return array_keys($this->getHiddenFields());
    }

    /**
     * @return \UWDOEM\Framework\Field\FieldInterface[]
     */
    public function getVisibleFields()
    {
        $base = $this->fields;
        $visibleFields = $this->getFieldsBase("getVisibleFields", $base);

        if (isset($this->visibleFieldNames)) {
            $visibleFields = array_intersect_key($visibleFields, array_flip($this->visibleFieldNames));
            $visibleFields = array_merge(array_flip($this->visibleFieldNames), $visibleFields);
        } elseif (isset($this->hiddenFieldNames)) {
            $visibleFields = array_diff_key($visibleFields, array_flip($this->hiddenFieldNames));
        }
        return $visibleFields;
    }

    /**
     * @return \UWDOEM\Framework\Field\FieldInterface[]
     */
    public function getHiddenFields()
    {
        // Begin with a set of hidden fields from our child fieldBearers...
        $hiddenFields = $this->getFieldsBase("getHiddenFields", []);

        // If we have specified which fields should be hidden for this field bearer...
        if (isset($this->hiddenFieldNames)) {
            // then get those fields should be hidden...
            $myHiddenFields = array_intersect_key($this->getFields(), array_flip($this->hiddenFieldNames));
            // and merge them into the list
            $hiddenFields = array_merge($hiddenFields, $myHiddenFields);
        }

        // If we have specified which should be visible...
        if (isset($this->visibleFieldNames)) {
            // then subtract those fields from the list of hidden fields
            $hiddenFields = array_diff_key($hiddenFields, array_flip($this->visibleFieldNames));
        }

        return $hiddenFields;
    }

    /**
     * Return the labels of the child fields.
     *
     * @return String[]
     */
    public function getFieldLabels()
    {
        return array_map(function ($field) {
            return $field->getLabel();

        }, $this->getFields());
    }

    /**
     * Given a field's string name, return the field.
     *
     * @param string $name
     * @return \UWDOEM\Framework\Field\FieldInterface
     * @throws \Exception
     */
    public function getFieldByName($name)
    {
        return $this->baseGetThingByName("Field", $name);
    }

    public function getNameByField($field)
    {

        $key = array_search($field, $this->getFields());
        if ($key === false) {
            throw new \Exception("Field not found among " . get_called_class() . "'s fields.");
        } else {
            return $key;
        }
    }

    protected function getLabelByFieldName($fieldName)
    {
        return $this->getFieldByName($fieldName)->getLabel();
    }

    public function getLabels()
    {
        return array_map([$this, 'getLabelByFieldName'], $this->getFieldNames());
    }

    public function getVisibleLabels()
    {
        return array_map([$this, 'getLabelByFieldName'], $this->getVisibleFieldNames());
    }

    public function getHiddenLabels()
    {
        return array_map([$this, 'getLabelByFieldName'], $this->getHiddenFieldNames());
    }

    public function getFieldBearers()
    {
        return $this->fieldBearers;
    }

    protected function baseGetThingByName($thingType, $name)
    {
        $getterName = "get" . $thingType . "s";

        if (method_exists($this, $getterName)) {
            $things = $this->$getterName();
        } else {
            throw new \Exception("Method get$thingType/ByName not not supported by class " .
                get_called_class() . " because class does not contain a $getterName method.");
        }

        if (array_key_exists($name, $things)) {
            return $things[$name];
        } else {
            $thingNames = implode(" ", array_keys($things));
            throw new \Exception("$thingType name $name not found among [$thingNames] of "
                . get_called_class() . "'s fieldBearers.");
        }
    }

    public function getFieldBearerByName($name)
    {
        return $this->baseGetThingByName("FieldBearer", $name);
    }

    public function save()
    {
        if (is_callable($this->saveFunction)) {
            $args = func_get_args();
            $args = array_merge([$this], $args);

            return call_user_func_array($this->saveFunction, $args);
        } else {
            return null;
        }
    }
}
