<?php

namespace Thomisticus\Generator\Generators\Common;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Utils\Database\Relationship;
use Thomisticus\Generator\Utils\Database\Table;
use Thomisticus\Generator\Utils\FileUtil;

class ModelGenerator extends BaseGenerator
{
    /**
     * @var CommandData
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
     * @param CommandData $commandData
     */
    public function __construct(CommandData $commandData)
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
        $templateData = get_template('api.model.model', 'app-generator');
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

        $primary = $this->commandData->getOption('primary') ?: $this->commandData->primaryKey;

        if (!empty($primary) && $primary !== 'id') {
            $primaryDocs = get_template('api.docs.model.model_primary', 'app-generator');
            $primaryDocs = $primaryDocs . generate_new_line_tab();
            $primary = $primaryDocs . "protected \$primaryKey = '" . strtolower($primary) . "';\n";
        } else {
            $primary = '';
        }

        $replacers = [
            '$PRIMARY$' => $primary,
            '$FIELDS$' => implode(',' . generate_new_line_tab(1, 2), $fillables),
            '$CAST$' => implode(',' . generate_new_line_tab(1, 2), $this->generateCasts()),
            '$RELATIONS$' => fill_template(
                $this->commandData->dynamicVars,
                implode(PHP_EOL . generate_new_line_tab(1, 1), $this->generateRelations())
            ),
            '$GENERATE_DATE$' => date('F j, Y, g:i a T')
        ];

        $templateData = str_replace(array_keys($replacers), $replacers, $templateData);

        return remove_duplicated_empty_lines($templateData);
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
                generate_tab() . "use SoftDeletes;",
                generate_new_line_tab() . "protected \$dates = ['" . $deletedAtTimestamp . "'];"
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
        $docsTemplate = get_template('api.docs.model.model', 'app-generator');
        $docsTemplate = fill_template($this->commandData->dynamicVars, $docsTemplate);

        $fillables = '';
        $fieldsArr = [];
        $count = 1;
        foreach ($this->commandData->relations as $relation) {
            $field = $relationText = $this->treatRelationshipFieldText($relation, $fieldsArr);

            if (in_array($field, $fieldsArr)) {
                $relationText .= '_' . $count;
                $count++;
            }

            $relationText = $relation->treatRelationFunctionName($relationText, $this->commandData->modelName);
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
     * @param \Thomisticus\Generator\Utils\Database\Relationship|null $relation
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
        $timestamps = Table::getTimestampFieldNames();

        $replace = '';
        if (empty($timestamps)) {
            $replace = generate_new_line_tab() . "public \$timestamps = false;\n";
        }

        if ($this->commandData->getOption('fromTable') && !empty($timestamps)) {
            list($createdAt, $updatedAt, $deletedAt) = collect($timestamps)->map(function ($field) {
                return !empty($field) ? "'$field'" : 'null';
            });

            if ($createdAt !== "'created_at'") {
                $replace .= get_template('api.docs.model.model_created_at', 'app-generator');
                $replace .= generate_new_line_tab() . "const CREATED_AT = $createdAt;\n\n";
            }

            if ($updatedAt !== "'updated_at'") {
                $replace .= get_template('api.docs.model.model_updated_at', 'app-generator');
                $replace .= generate_new_line_tab() . "const UPDATED_AT = $updatedAt;\n\n";
            }

            if ($deletedAt !== "'deleted_at'") {
                $replace .= get_template('api.docs.model.model_deleted_at', 'app-generator');
                $replace .= generate_new_line_tab() . "const DELETED_AT = $deletedAt;";
            }

            if (!empty($replace)) {
                $replace = generate_tab() . $replace;
            }
        }

        return str_replace('$TIMESTAMPS$', $replace, $templateData);
    }

    /**
     * Generates the attributes that should be casted to native types in the model
     * @return array
     */
    public function generateCasts()
    {
        $casts = [];

        $timestamps = Table::getTimestampFieldNames();

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
            $field = $relationText = $this->treatRelationshipFieldText($relation, $fieldsArr);

            if (in_array($field, $fieldsArr)) {
                $relationText .= '_' . $count;
                $count++;
            }

            $relationText = $relation->getRelationFunctionText($relationText, $this->commandData->modelName);
            if (!empty($relationText)) {
                $fieldsArr[] = $field;
                $relations[] = $relationText;
            }
        }

        return $relations;
    }

    /**
     * Treat the relationship field text considering the pivot table name and custom foreign key names
     * before generating the relation.
     * This method is useful to avoid weird method names like: "item1s()" and make it more readable.
     *
     * @param Relationship $relation
     * @param array $computedFields Fields already computed while creating the relationship models
     * @return mixed|string|null
     */
    private function treatRelationshipFieldText(Relationship $relation, array $computedFields = [])
    {
        $field = (isset($relation->inputs[0])) ? $relation->inputs[0] : null;
        $searchModelNames = array_reverse(Arr::only($this->commandData->config->modelNames, ['snake_plural', 'snake']));

        $relationFk = $relation->additionalParams['foreignKey'] ?? null;
        $relationOk = $relation->additionalParams['ownerKey'] ?? null;

        // If relationship is made with a custom name other than eg: 'table_name_id' and already in $computedFields
        if ($relationFk && in_array($relationFk, $computedFields) && !Str::contains($relationFk, $searchModelNames)) {
            $relationFkText = $relationOk ? str_replace($relationOk, '', $relationFk) : $relationFk;
            $field = model_name_from_table_name($relationFkText);
        }

        // If contains pivot table. Usually will enter here only for many to many relationships
        if (!empty($relation->inputs[1])) {
            $field = str_replace($searchModelNames, '', $relation->inputs[1]);
            $field = model_name_from_table_name($field);
        }

        return $field;
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
