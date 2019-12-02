<?php

namespace Thomisticus\Generator\Generators\Common;

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
            $relation->commandData = $this->commandData;
            $field = $relationText = $relation->treatRelationshipFieldText($relation);

            if (in_array($field, $fieldsArr)) {
                $relationText .= '_' . $count;
                $count++;
            }

            $relationText = $relation->getRelationAttributes($relationText, $this->commandData->modelName);
            $fillables .= ' * @property ' . $this->getPHPDocType($relation->type, $relation, $relationText['functionName']) . PHP_EOL;
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
        /** @var array $types $dbyType => ['fieldType', 'fieldFormat'] */
        $types = [
            'increments' => ['integer', 'int32'],
            'integer' => ['integer', 'int32'],
            'smallinteger' => ['integer', 'int32'],
            'long' => ['integer', 'int32'],
            'biginteger' => ['integer', 'int32'],
            'double' => ['number', 'number'],
            'float' => ['number', 'number'],
            'real' => ['number', 'number'],
            'decimal' => ['number', 'number'],
            'boolean' => ['boolean', null],
            'string' => ['string', null],
            'char' => ['string', null],
            'text' => ['string', null],
            'mediumtext' => ['string', null],
            'longtext' => ['string', null],
            'enum' => ['string', null],
            'byte' => ['string', 'byte'],
            'binary' => ['string', 'binary'],
            'password' => ['string', 'password'],
            'date' => ['string', 'date'],
            'datetime' => ['string', 'date-time'],
            'timestamp' => ['string', 'date-time']
        ];

        $type = $types[strtolower($dbType)];

        return [
            'fieldType' => $type[0],
            'fieldFormat' => $type[1]
        ];
    }

    /**
     * Retrieves the PHPDoc type for the field according to its database column type
     *
     * @param string $dbType
     * @param Relationship|null $relation
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

        $fieldTypesMap = [
            'integer' => 'integer',
            'increments' => 'integer',
            'smallinteger' => 'integer',
            'long' => 'integer',
            'biginteger' => 'integer',
            'double' => 'double',
            'float' => 'float',
            'decimal' => 'float',
            'boolean' => 'boolean',
            'datetime' => 'datetime',
            'datetimetz' => 'datetime',
            'date' => 'date',
            'enum' => 'string',
            'string' => 'string',
            'char' => 'string',
            'text' => 'string',
        ];

        foreach ($this->commandData->fields as $field) {
            if (!in_array($field->name, $timestamps)) {
                $rule = "'" . $field->name . "' => ";
                $fieldType = $fieldTypesMap[strtolower($field->fieldType)] ?? '';
                $rule .= "'" . $fieldType . "'";

                if (!empty($rule)) {
                    $casts[] = $rule;
                }
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
            $relation->commandData = $this->commandData;
            $field = $relationText = $relation->treatRelationshipFieldText($relation);

            if (in_array($field, $fieldsArr)) {
                $relationText .= '_' . $count;
                $count++;
            }

            $relationText = $relation->getRelationFunctionText($relationText);
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
