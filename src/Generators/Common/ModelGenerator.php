<?php

namespace Thomisticus\Generator\Generators\Common;

use Illuminate\Support\Str;
use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Utils\Database\GeneratorFieldRelation;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\FileUtil;
use Thomisticus\Generator\Utils\Database\TableFieldsGenerator;

class ModelGenerator extends BaseGenerator
{
    /**
     * @var \Thomisticus\Generator\Utils\CommandData
     */
    private $commandData;

    /**
     * Model file path
     * @var string
     */
    private $path;

    /**
     * Model file name
     * @var string
     */
    private $fileName;

    /**
     * ModelGenerator constructor.
     *
     * @param \Thomisticus\Generator\Utils\CommandData $commandData
     */
    public function __construct(\Thomisticus\Generator\Utils\CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->paths['model'];
        $this->fileName = $this->commandData->modelName . '.php';
        $this->commandData->dynamicVars['$TABLE_NAME$'] = strtolower($this->commandData->dynamicVars['$TABLE_NAME$']);
    }

    /**
     * Generates model file
     */
    public function generate()
    {
        $templateData = get_template('model.model', 'app-generator');
        $templateData = $this->fillTemplate($templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandObj->comment("\nModel created: ");
        $this->commandData->commandObj->info($this->fileName);
    }

    /**
     * Fills the model stub template. Including the soft deletes, phpdoc blocks,
     * timestamps, primary key, fields, rules, casts, relations and dates
     *
     * @param string $templateData
     * @return mixed|string
     */
    private function fillTemplate($templateData)
    {
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);
        $templateData = $this->fillSoftDeletes($templateData);

        $fillables = [];
        foreach ($this->commandData->fields as $field) {
            if ($field->isFillable) {
                $fillables[] = "'" . strtolower($field->name) . "'";
            }
        }

        $templateData = $this->fillDocs($templateData);
        $templateData = $this->fillTimestamps($templateData);

        if ($primary = $this->commandData->getOption('primary') ?: $this->commandData->primaryKey) {
            $primaryDocs = get_template('docs.model_primary', 'app-generator');
            $primary = $primaryDocs . generate_new_line_tab();
            $primary .= "protected \$primaryKey = '" . strtolower($primary) . "';\n";
        } else {
            $primary = '';
        }

        $replacers = [
            '$PRIMARY$' => $primary,
            '$FIELDS$' => implode(',' . generate_new_line_tab(1, 2), $fillables),
            '$RULES$' => implode(',' . generate_new_line_tab(1, 2), $this->generateRules()),
            '$CAST$' => implode(',' . generate_new_line_tab(1, 2), $this->generateCasts()),
            '$RELATIONS$' => fill_template(
                $this->commandData->dynamicVars,
                implode(PHP_EOL . generate_new_line_tab(1, 1), $this->generateRelations())
            ),
            '$GENERATE_DATE$' => date('F j, Y, g:i a T')
        ];

        return str_replace(array_keys($replacers), $replacers, $templateData);
    }

    /**
     * Fills the soft delete variables in the model
     *
     * @param string $templateData
     * @return string
     */
    private function fillSoftDeletes($templateData)
    {
        $softDeleteVariables = ['$SOFT_DELETE_IMPORT$', '$SOFT_DELETE$', '$SOFT_DELETE_DATES$'];

        if (!$this->commandData->getOption('softDelete')) {
            return str_replace($softDeleteVariables, '', $templateData);
        }

        $deletedAtTimestamp = config('app-generator.timestamps.deleted_at', 'deleted_at');

        return str_replace(
            $softDeleteVariables,
            [
                "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n",
                "use SoftDeletes;\n",
                generate_new_line_tab() . "protected \$dates = ['" . $deletedAtTimestamp . "'];\n"
            ],
            $templateData
        );
    }

    /**
     * Fills the PHPDoc blocks in the model
     *
     * @param string $templateData
     * @return mixed
     */
    private function fillDocs($templateData)
    {
        $docsTemplate = get_template('docs.model', 'app-generator');
        $docsTemplate = fill_template($this->commandData->dynamicVars, $docsTemplate);

        $fillables = '';
        $fieldsArr = [];
        $count = 1;
        foreach ($this->commandData->relations as $relation) {
            $field = $relationText = (isset($relation->inputs[0])) ? $relation->inputs[0] : null;
            if (in_array($field, $fieldsArr)) {
                $relationText = $relationText . '_' . $count;
                $count++;
            }

            $fillables .= ' * @property ' . $this->getPHPDocType($relation->type, $relation, $relationText) . PHP_EOL;
            $fieldsArr[] = $field;
        }

        foreach ($this->commandData->fields as $field) {
            if ($field->isFillable) {
                $fillables .= ' * @property ' . $this->getPHPDocType($field->fieldType);
                $fillables .= ' ' . strtolower($field->name) . PHP_EOL;
            }
        }

        $docsTemplate = str_replace(
            ['$GENERATE_DATE$', '$PHPDOC$'],
            [date('F j, Y, g:i a T'), $fillables],
            $docsTemplate
        );

        return str_replace('$DOCS$', $docsTemplate, $templateData);
    }

    /**
     * Retrieves the field type and format for PHPDoc block according to database column type
     *
     * @param string $dbType
     * @return array
     */
    public function getFieldType($dbType)
    {
        $fieldType = $fieldFormat = null;

        switch (strtolower($dbType)) {
            case 'increments':
            case 'integer':
            case 'smallinteger':
            case 'long':
            case 'biginteger':
                $fieldType = 'integer';
                $fieldFormat = 'int32';
                break;
            case 'double':
            case 'float':
            case 'real':
            case 'decimal':
                $fieldType = 'number';
                $fieldFormat = 'number';
                break;
            case 'boolean':
                $fieldType = 'boolean';
                break;
            case 'string':
            case 'char':
            case 'text':
            case 'mediumtext':
            case 'longtext':
            case 'enum':
                $fieldType = 'string';
                break;
            case 'byte':
                $fieldType = 'string';
                $fieldFormat = 'byte';
                break;
            case 'binary':
                $fieldType = 'string';
                $fieldFormat = 'binary';
                break;
            case 'password':
                $fieldType = 'string';
                $fieldFormat = 'password';
                break;
            case 'date':
                $fieldType = 'string';
                $fieldFormat = 'date';
                break;
            case 'datetime':
            case 'timestamp':
                $fieldType = 'string';
                $fieldFormat = 'date-time';
                break;
        }

        return compact('fieldType', 'fieldFormat');
    }

    /**
     * Retrieves the PHPDoc type for the field according to its database column type
     *
     * @param string $dbType
     * @param \Thomisticus\Generator\Utils\Database\GeneratorFieldRelation|null $relation
     * @param string|null $relationText
     *
     * @return string
     */
    private function getPHPDocType($dbType, $relation = null, $relationText = null)
    {
        $relationText = (!empty($relationText)) ? $relationText : null;

        switch ($dbType) {
            case 'datetime':
                return 'string|\Carbon\Carbon';
            case '1t1':
                return '\\' . $this->commandData->config->namespaces['model']
                    . '\\' . $relation->inputs[0] . ' ' . Str::camel($relationText);
            case 'mt1':
                $relationName = $relationText;
                if (isset($relation->inputs[1])) {
                    $relationName = str_replace('_id', '', strtolower($relation->inputs[1]));
                }

                return '\\' . $this->commandData->config->namespaces['model']
                    . '\\' . $relation->inputs[0] . ' ' . Str::camel($relationName);
            case '1tm':
            case 'mtm':
            case 'hmt':
                return '\Illuminate\Database\Eloquent\Collection' . ' ' . Str::camel(Str::plural($relationText));
            default:
                $fieldData = $this->getFieldType($dbType);
                return $fieldData['fieldType'] ?? $dbType;
        }
    }

    /**
     * Fill timestamps properties in the model
     *
     * @param string $templateData
     * @return mixed
     */
    private function fillTimestamps($templateData)
    {
        $timestamps = TableFieldsGenerator::getTimestampFieldNames();

        $replace = '';
        if (empty($timestamps)) {
            $replace = generate_new_line_tab() . "public \$timestamps = false;\n";
        }

        if ($this->commandData->getOption('fromTable') && !empty($timestamps)) {
            list($created_at, $updated_at, $deleted_at) = collect($timestamps)->map(function ($field) {
                return !empty($field) ? "'$field'" : 'null';
            });

            $replace .= get_template('docs.model_created_at', 'app-generator');
            $replace .= generate_new_line_tab() . "const CREATED_AT = $created_at;\n\n";
            $replace .= get_template('docs.model_updated_at', 'app-generator');
            $replace .= generate_new_line_tab() . "const UPDATED_AT = $updated_at;\n\n";
            $replace .= get_template('docs.model_deleted_at', 'app-generator');
            $replace .= generate_new_line_tab() . "const DELETED_AT = $deleted_at;";
        }

        return str_replace('$TIMESTAMPS$', $replace, $templateData);
    }

    /**
     * Generates the validation rules
     * @return array
     */
    private function generateRules()
    {
        if ($this->commandData->getOption('fromTable')) {
            return $this->generateRulesFromTable();
        }

        $dontRequireFields = config('app-generator.options.hidden_fields', [])
            + config('app-generator.options.excluded_fields', []);

        $rules = [];
        foreach ($this->commandData->fields as $field) {
            if (
                !$field->isPrimary && $field->isNotNull && empty($field->validations)
                && !in_array($field->name, $dontRequireFields)
            ) {
                $field->validations = 'required';
            }

            if (!empty($field->validations)) {
                if (Str::contains($field->validations, 'unique:')) {
                    $rule = explode('|', $field->validations);

                    // move unique rule to last
                    usort($rule, function ($record) {
                        return (Str::contains($record, 'unique:')) ? 1 : 0;
                    });

                    $field->validations = implode('|', $rule);
                }

                $rule = "'" . $field->name . "' => '" . $field->validations . "'";
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    /**
     * Generate validation rules when the command '--fromTable' is present
     * @return array
     */
    private function generateRulesFromTable()
    {
        $rules = [];
        $timestamps = TableFieldsGenerator::getTimestampFieldNames();

        foreach ($this->commandData->fields as $field) {
            if (in_array($field->name, $timestamps) || !$field->isFillable) {
                continue;
            }

            $rule = "'" . $field->name . "' => ";
            switch ($field->fieldType) {
                case 'integer':
                    $rule .= "'required|integer'";
                    break;
                case 'decimal':
                case 'double':
                case 'float':
                    $rule .= "'required|numeric'";
                    break;
                case 'boolean':
                    $rule .= "'required|boolean'";
                    break;
                case 'dateTime':
                case 'dateTimeTz':
                    $rule .= "'required|datetime'";
                    break;
                case 'date':
                    $rule .= "'required|date'";
                    break;
                case 'enum':
                case 'string':
                case 'char':
                case 'text':
                    $rule .= "'required|max:45'";
                    break;
                default:
                    $rule = '';
                    break;
            }

            if (!empty($rule)) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    /**
     * Generates the attributes that should be casted to native types in the model
     * @return array
     */
    public function generateCasts()
    {
        $casts = [];

        $timestamps = TableFieldsGenerator::getTimestampFieldNames();

        foreach ($this->commandData->fields as $field) {
            if (in_array($field->name, $timestamps)) {
                continue;
            }

            $rule = "'" . $field->name . "' => ";

            switch (strtolower($field->fieldType)) {
                case 'integer':
                case 'increments':
                case 'smallinteger':
                case 'long':
                case 'biginteger':
                    $rule .= "'integer'";
                    break;
                case 'double':
                    $rule .= "'double'";
                    break;
                case 'float':
                case 'decimal':
                    $rule .= "'float'";
                    break;
                case 'boolean':
                    $rule .= "'boolean'";
                    break;
                case 'datetime':
                case 'datetimetz':
                    $rule .= "'datetime'";
                    break;
                case 'date':
                    $rule .= "'date'";
                    break;
                case 'enum':
                case 'string':
                case 'char':
                case 'text':
                    $rule .= "'string'";
                    break;
                default:
                    $rule = '';
                    break;
            }

            if (!empty($rule)) {
                $casts[] = $rule;
            }
        }

        return $casts;
    }

    /**
     * Returns and array of relation methods for the model
     * @return array
     */
    private function generateRelations()
    {
        $relations = $fieldsArr = [];
        $count = 1;

        foreach ($this->commandData->relations as $relation) {
            $field = (isset($relation->inputs[0])) ? $relation->inputs[0] : null;

            $relationShipText = $field;
            if (in_array($field, $fieldsArr)) {
                $relationShipText = $relationShipText . '_' . $count;
                $count++;
            }

            $relationText = $relation->getRelationFunctionText($relationShipText);
            if (!empty($relationText)) {
                $fieldsArr[] = $field;
                $relations[] = $relationText;
            }
        }

        return $relations;
    }

    /**
     * Rollback the model file generation
     */
    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandObj->comment('Model file deleted: ' . $this->fileName);
        }
    }
}
