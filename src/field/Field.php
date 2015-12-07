<?php

namespace UWDOEM\Framework\Field;

use UWDOEM\Framework\Etc\StringUtils;
use UWDOEM\Framework\Visitor\VisitableTrait;
use DateTime;

/**
 * Class Field provides a small, typed data container for display and
 * user submission.
 *
 * @package UWDOEM\Framework\Field
 */
class Field implements FieldInterface
{

    const FIELD_TYPE_TEXT = "text";
    const FIELD_TYPE_TEXTAREA = "textarea";
    const FIELD_TYPE_BOOLEAN = "boolean";
    const FIELD_TYPE_CHOICE = "choice";
    const FIELD_TYPE_MULTIPLE_CHOICE = "multiple-choice";
    const FIELD_TYPE_LITERAL = "literal";
    const FIELD_TYPE_SECTION_LABEL = "section-label";
    const FIELD_TYPE_PRIMARY_KEY = "primary-key";
    const FIELD_TYPE_FOREIGN_KEY = "foreign-key";
    const FIELD_TYPE_AUTO_TIMESTAMP = "auto-timestamp";

    /** @var bool  */
    protected $required;

    /** @var int  */
    protected $fieldSize;

    /** @var string */
    protected $type;

    /** @var string */
    protected $label;

    /** @var string|string[]  */
    protected $initial;

    /** @var string[] */
    protected $fieldErrors = [];

    /** @var string[] */
    protected $prefixes = [];

    /** @var string[] */
    protected $suffixes = [];

    /** @var string */
    protected $validatedData;

    /** @var bool */
    protected $isValid;

    /** @var string[] */
    protected $choices;

    use VisitableTrait;


    public function getId()
    {
        return md5($this->getSlug());
    }

    /**
     * @param string $type
     * @param string $label
     * @param string|null $initial
     * @param bool|False $required
     * @param $choices
     * @param int $fieldSize
     */
    public function __construct($type, $label = "", $initial = "", $required = false, $choices = [], $fieldSize = 255)
    {
        $this->type = $type;
        $this->label = $label;

        $this->setInitial($initial);
        
        $this->required = $required;
        $this->choices = $choices;
        $this->fieldSize = $fieldSize;
    }

    /**
     * @return string
     */
    public function getSubmitted()
    {
        $fieldType = $this->getType();

        if ($fieldType == "checkbox") {
            $data = (string)array_key_exists($this->getSlug(), $_POST);
        } else {
            $data = array_key_exists($this->getSlug(), $_POST) ? $_POST[$this->getSlug()]: "";
        }

        if (in_array($fieldType, [static::FIELD_TYPE_CHOICE, static::FIELD_TYPE_MULTIPLE_CHOICE])) {
            $data = $this->parseChoiceSlugs($data);
        }

        return $data;
    }

    /**
     * @return bool
     */
    public function wasSubmitted()
    {
        return array_key_exists($this->getSlug(), $_POST) && $_POST[$this->getSlug()] !== "";
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string[]
     */
    public function getChoices()
    {
        return $this->choices;
    }

    /**
     * @return string[]
     */
    public function getChoiceSlugs()
    {
        return array_map(
            function ($choice) {
                return StringUtils::slugify($choice);
            },
            $this->choices
        );
    }

    /**
     * @param array $choices
     */
    public function setChoices(array $choices)
    {
        $this->choices = $choices;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->fieldSize;
    }

    /**
     * @param int $size
     */
    public function setSize($size)
    {
        $this->fieldSize = $size;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @param string $suffix
     */
    public function addSuffix($suffix)
    {
        $this->suffixes[] = $suffix;
    }

    /**
     * @return string[]
     */
    public function getSuffixes()
    {
        return $this->suffixes;
    }

    /**
     * @param string $prefix
     */
    public function addPrefix($prefix)
    {
        $this->prefixes[] = $prefix;
    }

    /**
     * @return string[]
     */
    public function getPrefixes()
    {
        return $this->prefixes;
    }

    /**
     * @return string
     */
    public function getLabelSlug()
    {
        return StringUtils::slugify($this->label);
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return implode("-", array_merge($this->getPrefixes(), [$this->getLabelSlug()], $this->getSuffixes()));
    }

    /**
     * @param string $value
     */
    public function setInitial($value)
    {
        if ($value instanceof DateTime) {
            $value = new DateTimeWrapper($value->format('Y-m-d'));
        }

        $this->initial = $value;
    }

    /**
     * @return string
     */
    public function getInitial()
    {
        return $this->initial;
    }

    /**
     * @param string $error
     */
    public function addError($error)
    {
        $this->fieldErrors[] = $error;
    }

    /**
     * @return string[]
     */
    public function getErrors()
    {
        return $this->fieldErrors;
    }

    /**
     * @return null
     */
    public function removeErrors()
    {
        $this->fieldErrors = [];
    }

    /**
     * @return null
     */
    public function validate()
    {

        $data = $this->wasSubmitted() ? $this->getSubmitted() : null;

        // Invalid selection on choice/multiple choice field
        if ($data === []) {
            $this->addError("Unrecognized choice.");
        }

        if ($this->isRequired() && is_null($data)) {
            $this->addError("This field is required.");
        }

        if (!$this->getErrors()) {
            $this->setValidatedData($data);
        }
    }

    /**
     * @return bool
     */
    protected function hasChoices()
    {
        return (bool)$this->getChoices();
    }

    protected function parseChoiceSlugs($slugs)
    {
        $choices = array_combine($this->getChoiceSlugs(), $this->getChoices());

        if ($this->getType() === static::FIELD_TYPE_CHOICE) {
            $slugs = [$slugs];
        }

        $result = [];
        foreach ($slugs as $choiceSlug) {
            if (array_key_exists($choiceSlug, $choices)) {
                $result[] = $choices[$choiceSlug];
            }
        }

        if ($this->getType() === static::FIELD_TYPE_CHOICE && !empty($result)) {
            $result = $result[0];
        }

        return $result;

    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required && $this->getType() != "hidden";
    }

    /**
     * @param bool $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return empty($this->fieldErrors);
    }

    /**
     * @param string $data
     */
    public function setValidatedData($data)
    {
        $this->validatedData = $data;
    }

    /**
     * @return string
     */
    public function getValidatedData()
    {
        return $this->validatedData;
    }
}
