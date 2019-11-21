<?php

namespace Thomisticus\Generator\Utils\Database;

use Doctrine\DBAL\Schema\Column;
use Illuminate\Support\Str;

class Field
{
    /**
     * Field name
     * @var string
     */
    public $name;

    /**
     * DB input for the column
     * @var string
     */
    public $dbInput;

    /**
     * Html input
     * @var string
     */
    public $htmlInput;

    /**
     * Html input type
     * @var string
     */
    public $htmlType;

    /**
     * Field type on database
     * @var string
     */
    public $fieldType;

    /**
     * @var array
     */
    public $htmlValues;

    /**
     * Migration text to fill the database migration file
     * @var string
     */
    public $migrationText;

    /**
     * Foreign Key text for database migration file
     * @var string
     */
    public $foreignKeyText;

    /**
     * Field validations
     * @var string
     */
    public $validations;

    /**
     * Whether the field is searchable or not
     * @var bool
     */
    public $isSearchable = true;

    /**
     * Whether the field is fillable or not
     * @var bool
     */
    public $isFillable = true;

    /**
     * Whether the field is primary key or not
     * @var bool
     */
    public $isPrimary = false;

    /**
     * Whether the field is unique key or not
     * @var bool
     */
    public $isUnique = false;

    /**
     * Whether the field is in the form or not
     * @var bool
     */
    public $inForm = true;

    /**
     * Whether the field is in index or not
     * @var bool
     */
    public $inIndex = true;

    /**
     * Whether the field is in the view or not
     * @var bool
     */
    public $inView = true;

    /**
     * Whether the field is null or not
     * @var bool
     */
    public $isNotNull = false;

    /**
     * Field description (comments from table)
     * @var string
     */
    public $description;

    /**
     * Field lenght
     * @var int|null
     */
    public $length;

    /**
     * Set $dbInput parsing the DB type from database columns
     *
     * @param string $dbInput
     * @param Column|null $column
     * @return $this
     */
    public function parseDBType($dbInput, $column = null)
    {
        $this->dbInput = $dbInput;
        if (!is_null($column)) {
            $this->length = $column->getLength();
            $this->dbInput = ($this->length > 0) ? $this->dbInput . ',' . $this->length : $this->dbInput;
            $this->dbInput = (!$column->getNotnull()) ? $this->dbInput . ':nullable' : $this->dbInput;
        }
        $this->prepareMigrationText();

        return $this;
    }

    /**
     * Parse HTML input type setting $htmlInput, $htmlType and $htmlValues properties
     *
     * @param string $htmlInput
     */
    public function parseHtmlInput($htmlInput)
    {
        $this->htmlInput = $htmlInput;
        $this->htmlValues = [];

        if (empty($htmlInput)) {
            $this->htmlType = 'text';
            return;
        }

        $inputsArr = explode(',', $htmlInput);
        $this->htmlType = array_shift($inputsArr);

        if (count($inputsArr) > 0) {
            $this->htmlValues = $inputsArr;
        }
    }

    /**
     * Parse options when generating from console.
     * Since majority of properties are "true" by default, all the properties
     * passed here will be considered as false (primary is an exception).
     *
     * @param string $options Options string. Eg: searchable,fillable,inForm
     * @return $this
     */
    public function parseOptions($options)
    {
        $options = strtolower($options);
        $optionsArr = explode(',', $options);

        $this->isSearchable = !in_array('searchable', $optionsArr);
        $this->isFillable = !in_array('fillable', $optionsArr);
        $this->inForm = !in_array('inForm', $optionsArr);
        $this->inIndex = !in_array('inIndex', $optionsArr);
        $this->inView = !in_array('inView', $optionsArr);

        if (in_array('primary', $optionsArr)) {
            // If field is primary key, then its not searchable, fillable, not in index nor in form
            $this->isPrimary = true;
            $this->isSearchable = $this->isFillable = $this->inForm = $this->inIndex = $this->inView = false;
        }

        return $this;
    }

    /**
     * Generates migration file text and sets in $migrationText property
     */
    private function prepareMigrationText()
    {
        $inputsArr = explode(':', $this->dbInput);
        $this->migrationText = '$table->';

        $fieldTypeParams = explode(',', array_shift($inputsArr));
        $this->fieldType = array_shift($fieldTypeParams);
        $this->migrationText .= $this->fieldType . "('" . $this->name . "'";

        if ($this->fieldType == 'enum') {
            $this->migrationText .= ', [';

            foreach ($fieldTypeParams as $param) {
                $this->migrationText .= "'" . $param . "',";
            }

            $this->migrationText = substr($this->migrationText, 0, strlen($this->migrationText) - 1) . ']';
        } else {
            foreach ($fieldTypeParams as $param) {
                $this->migrationText .= ', ' . $param;
            }
        }

        $this->migrationText .= ')';

        foreach ($inputsArr as $input) {
            $inputParams = explode(',', $input);
            $functionName = array_shift($inputParams);
            if ($functionName == 'foreign') {
                $foreignTable = array_shift($inputParams);
                $foreignField = array_shift($inputParams);
                $this->foreignKeyText .= "\$table->foreign('" . $this->name . "')";
                $this->foreignKeyText .= "->references('" . $foreignField . "')->on('" . $foreignTable . "');";
            } else {
                $this->migrationText .= '->' . $functionName . '(' . implode(', ', $inputParams) . ')';
            }
        }

        $this->migrationText .= ';';
    }

    /**
     * Parse fields from Json file
     *
     * @param array $fieldInput
     * @return Field
     */
    public static function parseFieldFromFile($fieldInput)
    {
        $field = new self();
        $field->name = $fieldInput['name'];
        $field->parseDBType($fieldInput['dbType']);
        $field->parseHtmlInput($fieldInput['htmlType'] ?? '');
        $field->validations = $fieldInput['validations'] ?? '';
        $field->isSearchable = $fieldInput['searchable'] ?? false;
        $field->isFillable = $fieldInput['fillable'] ?? true;
        $field->isPrimary = $fieldInput['primary'] ?? false;
        $field->inForm = $fieldInput['inForm'] ?? true;
        $field->inIndex = $fieldInput['inIndex'] ?? true;
        $field->inView = $fieldInput['inView'] ?? true;

        return $field;
    }

    /**
     * Magic method to access Field properties
     *
     * @param string $key Property name
     * @return mixed|string
     */
    public function __get($key)
    {
        if ($key == 'fieldTitle') {
            return Str::title(str_replace('_', ' ', $this->name));
        }

        return $this->$key;
    }
}
