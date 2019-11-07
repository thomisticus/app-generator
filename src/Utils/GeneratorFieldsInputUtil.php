<?php

namespace Thomisticus\Generator\Utils;

use Thomisticus\Generator\Common\GeneratorField;

class GeneratorFieldsInputUtil
{
    /**
     * Validates the inputs added in the console command
     *
     * @param array $fieldInputStr
     * @return bool
     */
    public static function validateFieldInput($fieldInputStr)
    {
        $fieldInputs = explode(' ', $fieldInputStr);

        if (count($fieldInputs) < 2) {
            return false;
        }

        return true;
    }

    /**
     * Field Input Format: field_name <space> db_type <space> html_type(optional) <space> options(optional)
     * Options are to skip the field from certain criteria like searchable, fillable, not in form, not in index
     * Searchable (searchable), Fillable (fillable), In Form (inForm), In Index (inIndex)
     * Sample Field Inputs
     *
     * title string text
     * body text textarea
     * name string,20 text
     * post_id integer:unsigned:nullable
     * post_id integer:unsigned:nullable:foreign,posts,id
     * password string text inForm,inIndex,searchable - options will skip field from being added in form, in index and searchable
     *
     * @param string $fieldInput
     * @param string $validations
     *
     * @return GeneratorField
     */
    public static function processFieldInput($fieldInput, $validations)
    {
        $fieldInputsArr = explode(' ', $fieldInput);

        $field = new GeneratorField();
        $field->name = $fieldInputsArr[0];
        $field->parseDBType($fieldInputsArr[1]);

        if (count($fieldInputsArr) > 2) {
            $field->parseHtmlInput($fieldInputsArr[2]);
        }

        if (count($fieldInputsArr) > 3) {
            $field->parseOptions($fieldInputsArr[3]);
        }

        $field->validations = $validations;

        return $field;
    }

    /**
     * Transform an array to a string structured as array for file replacing
     *
     * @param array $array
     * @return string
     */
    public static function prepareKeyValueArrayString($array)
    {
        $arrStr = '[';
        foreach ($array as $key => $item) {
            $arrStr .= "'$item' => '$key', ";
        }

        return substr($arrStr, 0, strlen($arrStr) - 2) . ']';
    }

    /**
     * Transform an array values to a string structured as array for file replacing
     *
     * @param array $array
     * @return string
     */
    public static function prepareValuesArrayString($array)
    {
        $arrStr = '[';
        foreach ($array as $item) {
            $arrStr .= "'$item', ";
        }

        return substr($arrStr, 0, strlen($arrStr) - 2) . ']';
    }

    /**
     * @param array $values
     * @return array
     */
    public static function prepareKeyValueArrayFromLabelValueString($values)
    {
        $arr = [];

        foreach ($values as $value) {
            $labelValue = explode(':', $value);

            if (count($labelValue) > 1) {
                $arr[$labelValue[0]] = $labelValue[1];
            } else {
                $arr[$labelValue[0]] = $labelValue[0];
            }
        }

        return $arr;
    }
}
