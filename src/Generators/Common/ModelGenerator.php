<?php

namespace Thomisticus\Generator\Generators\Common;

use Illuminate\Support\Str;
use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Common\GeneratorFieldRelation;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\FileUtil;
use Thomisticus\Generator\Utils\TableFieldsGenerator;

class ModelGenerator extends BaseGenerator
{
    /**
     * Fields not included in the generator by default.
     *
     * @var array
     */
    protected $excluded_fields = [
        'created_at',
        'updated_at',
    ];

    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;
    private $fileName;
    private $table;

    /**
     * ModelGenerator constructor.
     *
     * @param \Thomisticus\Generator\Common\CommandData $commandData
     */
    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->pathModel;
        $this->fileName = $this->commandData->modelName . '.php';
        $this->commandData->dynamicVars['$TABLE_NAME$'] = strtolower($this->commandData->dynamicVars['$TABLE_NAME$']);
    }

    public function generate()
    {
        $templateData = get_template('model.model', 'app-generator');

        $templateData = $this->fillTemplate($templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandComment("\nModel created: ");
        $this->commandData->commandInfo($this->fileName);
    }

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
            $primary = $primaryDocs . generate_new_line_tab() . "protected \$primaryKey = '" . strtolower($primary) . "';\n";
        } else {
            $primary = '';
        }

        $templateData = str_replace('$PRIMARY$', $primary, $templateData);

        $templateData = str_replace('$FIELDS$', implode(',' . generate_new_line_tab(1, 2), $fillables), $templateData);

        $templateData = str_replace(
            '$RULES$',
            implode(',' . generate_new_line_tab(1, 2), $this->generateRules()),
            $templateData
        );

        $templateData = str_replace(
            '$CAST$',
            implode(',' . generate_new_line_tab(1, 2), $this->generateCasts()),
            $templateData
        );

        $templateData = str_replace(
            '$RELATIONS$',
            fill_template(
                $this->commandData->dynamicVars,
                implode(PHP_EOL . generate_new_line_tab(1, 1), $this->generateRelations())
            ),
            $templateData
        );

        $templateData = str_replace('$GENERATE_DATE$', date('F j, Y, g:i a T'), $templateData);

        return $templateData;
    }

    private function fillSoftDeletes($templateData)
    {
        if (!$this->commandData->getOption('softDelete')) {
            $templateData = str_replace('$SOFT_DELETE_IMPORT$', '', $templateData);
            $templateData = str_replace('$SOFT_DELETE$', '', $templateData);
            $templateData = str_replace('$SOFT_DELETE_DATES$', '', $templateData);
        } else {
            $templateData = str_replace(
                '$SOFT_DELETE_IMPORT$',
                "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n",
                $templateData
            );
            $templateData = str_replace('$SOFT_DELETE$', generate_tab() . "use SoftDeletes;\n", $templateData);
            $deletedAtTimestamp = config('app-generator.timestamps.deleted_at', 'deleted_at');
            $templateData = str_replace(
                '$SOFT_DELETE_DATES$',
                generate_new_line_tab() . "protected \$dates = ['" . $deletedAtTimestamp . "'];\n",
                $templateData
            );
        }

        return $templateData;
    }

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
                $fillables .= ' * @property ' . $this->getPHPDocType($field->fieldType) . ' ' . strtolower($field->name) . PHP_EOL;
            }
        }
        $docsTemplate = str_replace('$GENERATE_DATE$', date('F j, Y, g:i a T'), $docsTemplate);
        $docsTemplate = str_replace('$PHPDOC$', $fillables, $docsTemplate);

        $templateData = str_replace('$DOCS$', $docsTemplate, $templateData);

        return $templateData;
    }

    public function getFieldType($type)
    {
        $fieldType = null;
        $fieldFormat = null;
        switch (strtolower($type)) {
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

        return ['fieldType' => $fieldType, 'fieldFormat' => $fieldFormat];
    }

    /**
     * @param $db_type
     * @param GeneratorFieldRelation|null $relation
     * @param string|null $relationText
     *
     * @return string
     */
    private function getPHPDocType($db_type, $relation = null, $relationText = null)
    {
        $relationText = (!empty($relationText)) ? $relationText : null;

        switch ($db_type) {
            case 'datetime':
                return 'string|\Carbon\Carbon';
            case '1t1':
                return '\\' . $this->commandData->config->nsModel . '\\' . $relation->inputs[0] . ' ' . Str::camel($relationText);
            case 'mt1':
                if (isset($relation->inputs[1])) {
                    $relationName = str_replace('_id', '', strtolower($relation->inputs[1]));
                } else {
                    $relationName = $relationText;
                }

                return '\\' . $this->commandData->config->nsModel . '\\' . $relation->inputs[0] . ' ' . Str::camel($relationName);
            case '1tm':
            case 'mtm':
            case 'hmt':
                return '\Illuminate\Database\Eloquent\Collection' . ' ' . Str::camel(Str::plural($relationText));
            default:
                $fieldData = $this->getFieldType($db_type);
                if (!empty($fieldData['fieldType'])) {
                    return $fieldData['fieldType'];
                }

                return $db_type;
        }
    }

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

    private function generateRules()
    {
        $dont_require_fields = config('app-generator.options.hidden_fields', [])
            + config('app-generator.options.excluded_fields', []);

        $rules = [];

        if ($this->commandData->getOption('fromTable')) {
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
        } else {
            foreach ($this->commandData->fields as $field) {
                if (
                    !$field->isPrimary
                    && $field->isNotNull
                    && empty($field->validations)
                    && !in_array($field->name, $dont_require_fields)
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
        }

        return $rules;
    }

    public function generateUniqueRules()
    {
        $tableNameSingular = Str::singular($this->commandData->config->tableName);
        $uniqueRules = '';
        foreach ($this->generateRules() as $rule) {
            if (Str::contains($rule, 'unique:')) {
                $rule = explode('=>', $rule);
                $string = '$rules[' . trim($rule[0]) . '].","';
                $uniqueRules .= '$rules[' . trim($rule[0]) . '] = ' . $string . '.$this->route("' . $tableNameSingular . '");';
            }
        }
        return $uniqueRules;
    }

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

    private function generateRelations()
    {
        $relations = [];

        $count = 1;
        $fieldsArr = [];
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

    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandComment('Model file deleted: ' . $this->fileName);
        }
    }
}