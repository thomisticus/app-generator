<?php

namespace Thomisticus\Generator\Utils\Database;

use DB;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Support\Str;
use Thomisticus\Generator\Utils\Database\GeneratorField;
use Thomisticus\Generator\Utils\Database\GeneratorFieldRelation;
use Thomisticus\Generator\Utils\Database\GeneratorForeignKey;
use Thomisticus\Generator\Utils\Database\GeneratorTable;

class TableFieldsGenerator
{
    /**
     * Database table name
     * @var string
     */
    public $tableName;

    /**
     * Primary key name
     * @var string|null
     */
    public $primaryKey;

    /**
     * Whether the field is searchable or not
     * @var boolean
     */
    public $defaultSearchable;

    /**
     * Table timestamps
     * @var array
     */
    public $timestamps;

    /**
     * @var AbstractSchemaManager
     */
    private $schemaManager;

    /**
     * @var Column[]
     */
    private $columns;

    /**
     * Table fields
     * @var \Thomisticus\Generator\Utils\Database\GeneratorField[]
     */
    public $fields;

    /**
     * Table relationships
     * @var GeneratorFieldRelation[]
     */
    public $relations;

    /**
     * Fields to be ignored
     * @var array
     */
    public $ignoredFields;

    /**
     * TableFieldsGenerator constructor.
     * @param string $tableName
     * @param array $ignoredFields
     */
    public function __construct($tableName, $ignoredFields)
    {
        $this->tableName = $tableName;
        $this->ignoredFields = $ignoredFields;

        $this->schemaManager = DB::getDoctrineSchemaManager();
        $platform = $this->schemaManager->getDatabasePlatform();
        $defaultMappings = [
            'enum' => 'string',
            'json' => 'text',
            'bit' => 'boolean',
        ];

        $mappings = config('app-generator.from_table.doctrine_mappings', []);
        $mappings = array_merge($mappings, $defaultMappings);
        foreach ($mappings as $dbType => $doctrineType) {
            $platform->registerDoctrineTypeMapping($dbType, $doctrineType);
        }

        $columns = $this->schemaManager->listTableColumns($tableName);

        $this->columns = [];
        foreach ($columns as $column) {
            if (!in_array($column->getName(), $ignoredFields)) {
                $this->columns[] = $column;
            }
        }

        $this->primaryKey = static::getPrimaryKeyOfTable($tableName);
        $this->timestamps = static::getTimestampFieldNames();
        $this->defaultSearchable = config('app-generator.options.tables_searchable_default', false);
    }

    /**
     * Prepares array of GeneratorField from table columns.
     */
    public function prepareFieldsFromTable()
    {
        foreach ($this->columns as $column) {
            $type = $column->getType()->getName();

            switch ($type) {
                case 'integer':
                    $field = $this->generateIntFieldInput($column, 'integer');
                    break;
                case 'smallint':
                    $field = $this->generateIntFieldInput($column, 'smallInteger');
                    break;
                case 'bigint':
                    $field = $this->generateIntFieldInput($column, 'bigInteger');
                    break;
                case 'boolean':
                    $name = Str::title(str_replace('_', ' ', $column->getName()));
                    $field = $this->generateField($column, 'boolean', 'checkbox,1');
                    break;
                case 'datetime':
                    $field = $this->generateField($column, 'datetime', 'date');
                    break;
                case 'datetimetz':
                    $field = $this->generateField($column, 'dateTimeTz', 'date');
                    break;
                case 'date':
                    $field = $this->generateField($column, 'date', 'date');
                    break;
                case 'time':
                    $field = $this->generateField($column, 'time', 'text');
                    break;
                case 'decimal':
                    $field = $this->generateNumberInput($column, 'decimal');
                    break;
                case 'float':
                    $field = $this->generateNumberInput($column, 'float');
                    break;
                case 'text':
                    $field = $this->generateField($column, 'text', 'textarea');
                    break;
                case 'string':
                default:
                    $field = $this->generateField($column, 'string', 'text');
                    break;
            }

            if (strtolower($field->name) == 'password') {
                $field->htmlType = 'password';
            } elseif (strtolower($field->name) == 'email') {
                $field->htmlType = 'email';
            } elseif (in_array(strtolower($field->name), array_map('strtolower', $this->timestamps))) {
                $field->isSearchable = $field->isFillable = $field->inForm = $field->inIndex = $field->inView = false;
            }

            $field->isNotNull = (bool)$column->getNotNull();
            $field->description = $column->getComment(); // get comments from table

            $this->fields[] = $field;
        }

        return $this;
    }

    /**
     * Get primary key of given table.
     *
     * @param string $tableName
     *
     * @return string|null The column name of the (simple) primary key
     */
    public static function getPrimaryKeyOfTable($tableName)
    {
        $schema = DB::getDoctrineSchemaManager();
        $column = $schema->listTableDetails($tableName)->getPrimaryKey();

        return $column ? $column->getColumns()[0] : '';
    }

    /**
     * Get timestamp columns from config.
     *
     * @return array the set of [created_at column name, updated_at column name]
     */
    public static function getTimestampFieldNames()
    {
        if (!config('app-generator.timestamps.enabled', true)) {
            return [];
        }

        return [
            config('app-generator.timestamps.created_at', 'created_at'),
            config('app-generator.timestamps.updated_at', 'updated_at'),
            config('app-generator.timestamps.deleted_at', 'deleted_at')
        ];
    }

    /**
     * Generates integer text field for database.
     *
     * @param string $dbType
     * @param Column $column
     *
     * @return \Thomisticus\Generator\Utils\Database\GeneratorField
     */
    private function generateIntFieldInput($column, $dbType)
    {
        $field = new GeneratorField();
        $field->name = $column->getName();
        $field->parseDBType($dbType);
        $field->htmlType = 'number';

        $field->dbInput .= $column->getAutoincrement() ? ',true' : ',false';

        if ($column->getUnsigned()) {
            $field->dbInput .= ',true';
        }

        return $this->checkForPrimary($field);
    }

    /**
     * Check if key is primary key and sets field options.
     *
     * @param \Thomisticus\Generator\Utils\Database\GeneratorField $field
     *
     * @return \Thomisticus\Generator\Utils\Database\GeneratorField
     */
    private function checkForPrimary(GeneratorField $field)
    {
        if ($field->name == $this->primaryKey) {
            $field->isPrimary = true;
            $field->isFillable = $field->isSearchable = $field->inIndex = $field->inForm = false;
        }

        return $field;
    }

    /**
     * Generates field.
     *
     * @param \Doctrine\DBAL\Schema\Column $column
     * @param                              $dbType
     * @param                              $htmlType
     *
     * @return \Thomisticus\Generator\Utils\Database\GeneratorField
     */
    private function generateField($column, $dbType, $htmlType)
    {
        $field = new GeneratorField();
        $field->name = $column->getName();
        $field->parseDBType($dbType, $column);
        $field->parseHtmlInput($htmlType);

        return $this->checkForPrimary($field);
    }

    /**
     * Generates number field.
     *
     * @param \Doctrine\DBAL\Schema\Column $column
     * @param string $dbType
     *
     * @return \Thomisticus\Generator\Utils\Database\GeneratorField
     */
    private function generateNumberInput($column, $dbType)
    {
        $field = new GeneratorField();
        $field->name = $column->getName();
        $field->parseDBType($dbType . ',' . $column->getPrecision() . ',' . $column->getScale());
        $field->htmlType = 'number';

        return $this->checkForPrimary($field);
    }

    /**
     * Prepares relations (GeneratorFieldRelation) array from table foreign keys.
     * @return $this
     */
    public function prepareRelations()
    {
        $foreignKeys = $this->prepareForeignKeys();
        $this->checkForRelations($foreignKeys);

        return $this;
    }

    /**
     * Prepares foreign keys from table with required details.
     *
     * @return \Thomisticus\Generator\Utils\Database\GeneratorTable[]
     */
    public function prepareForeignKeys()
    {
        $tables = $this->schemaManager->listTables();

        $fields = [];

        foreach ($tables as $table) {
            if ($primaryKey = $table->getPrimaryKey()) {
                $primaryKey = $primaryKey->getColumns()[0];
            }
            $formattedForeignKeys = [];
            $tableForeignKeys = $table->getForeignKeys();
            foreach ($tableForeignKeys as $tableForeignKey) {
                $tableForeignKey = [
                    'name' => $tableForeignKey->getName(),
                    'localField' => $tableForeignKey->getLocalColumns()[0],
                    'foreignField' => $tableForeignKey->getForeignColumns()[0],
                    'foreignTable' => $tableForeignKey->getForeignTableName(),
                    'onUpdate' => $tableForeignKey->onUpdate(),
                    'onDelete' => $tableForeignKey->onDelete(),
                ];

                $formattedForeignKeys[] = new GeneratorForeignKey(...array_values($tableForeignKey));
            }

            $generatorTable = new GeneratorTable($primaryKey, $formattedForeignKeys);

            $fields[$table->getName()] = $generatorTable;
        }

        return $fields;
    }

    /**
     * Prepares relations array from table foreign keys.
     *
     * @param GeneratorTable[] $tables
     */
    private function checkForRelations($tables)
    {
        // get Model table name and table details from tables list
        $modelTableName = $this->tableName;
        $modelTable = $tables[$modelTableName];
        unset($tables[$modelTableName]);

        $this->relations = [];

        // detects many to one rules for model table
        $manyToOneRelations = $this->detectManyToOne($tables, $modelTable);

        if (count($manyToOneRelations) > 0) {
            $this->relations = array_merge($this->relations, $manyToOneRelations);
        }

        foreach ($tables as $tableName => $table) {
            $foreignKeys = $table->foreignKeys;
            $primary = $table->primaryKey;

            // if foreign key count is 2 then check if many to many relationship is there
            if (count($foreignKeys) == 2) {
                $manyToManyRelation = $this->isManyToMany($tables, $tableName, $modelTable, $modelTableName);
                if ($manyToManyRelation) {
                    $this->relations[] = $manyToManyRelation;
                    continue;
                }
            }

            // iterate each foreign key and check for relationship
            foreach ($foreignKeys as $foreignKey) {
                // check if foreign key is on the model table for which we are using generator command
                if ($foreignKey->foreignTable == $modelTableName) {
                    // detect if one to one relationship is there
                    $isOneToOne = $this->isOneToOne($primary, $foreignKey, $modelTable->primaryKey);
                    if ($isOneToOne) {
                        $modelName = model_name_from_table_name($tableName);
                        $this->relations[] = GeneratorFieldRelation::parseRelation('1t1,' . $modelName);
                        continue;
                    }

                    // detect if one to many relationship is there
                    $isOneToMany = $this->isOneToMany($primary, $foreignKey, $modelTable->primaryKey);
                    if ($isOneToMany) {
                        $additionalParams = [];
                        if (!empty($foreignKey->localField) && !empty($foreignKey->foreignField)) {
                            $additionalParams = [
                                'foreignKey' => $foreignKey->localField,
                                'localKey' => $foreignKey->foreignField
                            ];
                        }

                        $modelName = model_name_from_table_name($tableName);
                        $this->relations[] = GeneratorFieldRelation::parseRelation(
                            '1tm,' . $modelName,
                            $additionalParams
                        );
                        continue;
                    }
                }
            }
        }
    }

    /**
     * Detects many to many relationship
     * If table has only two foreign keys
     * Both foreign keys are primary key in foreign table
     * Also one is from model table and one is from diff table.
     *
     * @param GeneratorTable[] $tables
     * @param string $tableName
     * @param GeneratorTable $modelTable
     * @param string $modelTableName
     *
     * @return bool|\Thomisticus\Generator\Utils\Database\GeneratorFieldRelation
     */
    private function isManyToMany($tables, $tableName, $modelTable, $modelTableName)
    {
        // get table details
        $table = $tables[$tableName];

        $isAnyKeyOnModelTable = false;

        // many to many model table name
        $manyToManyTable = '';

        $foreignKeys = $table->foreignKeys;
        $primary = $table->primaryKey;

        // check if any foreign key is there from model table
        foreach ($foreignKeys as $foreignKey) {
            if ($foreignKey->foreignTable == $modelTableName) {
                $isAnyKeyOnModelTable = true;
            }
        }

        // if foreign key is there
        if (!$isAnyKeyOnModelTable) {
            return false;
        }

        $additionalParams = [];

        // if foreign key is there
        if ($isAnyKeyOnModelTable) {
            foreach ($foreignKeys as $foreignKey) {
                $foreignField = $foreignKey->foreignField; // cd_fabrica_software
                $foreignTableName = $foreignKey->foreignTable; // tb_fabrica_software

                // if foreign table is model table
                if ($foreignTableName == $modelTableName) {
                    $foreignTable = $modelTable;
                } else {
                    $foreignTable = $tables[$foreignTableName];
                    // get the many to many model table name
                    $manyToManyTable = $foreignTableName;
                }

                if ($foreignKey->foreignField == $this->primaryKey) {
                    $additionalParams['foreignPivotKey'] = $foreignKey->localField;
                } else {
                    $additionalParams['relatedPivotKey'] = $foreignKey->localField;
                }

                // if foreign field is not primary key of foreign table
                // then it can not be many to many
                if ($foreignField != $foreignTable->primaryKey) {
                    return false;
                    break;
                }

                // if foreign field is primary key of this table
                // then it can not be many to many
                if ($foreignField == $primary) {
                    return false;
                }
            }
        }

        $modelName = model_name_from_table_name($manyToManyTable);

        return GeneratorFieldRelation::parseRelation('mtm,' . $modelName . ',' . $tableName, $additionalParams);
    }

    /**
     * Detects if one to one relationship is there
     * If foreign key of table is primary key of foreign table
     * Also foreign key field is primary key of this table.
     *
     * @param string $primaryKey
     * @param \Thomisticus\Generator\Utils\Database\GeneratorForeignKey $foreignKey
     * @param string $modelTablePrimary
     *
     * @return bool
     */
    private function isOneToOne($primaryKey, $foreignKey, $modelTablePrimary)
    {
        if ($foreignKey->foreignField == $modelTablePrimary) {
            if ($foreignKey->localField == $primaryKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detects if one to many relationship is there
     * If foreign key of table is primary key of foreign table
     * Also foreign key field is not primary key of this table.
     *
     * @param string $primaryKey
     * @param \Thomisticus\Generator\Utils\Database\GeneratorForeignKey $foreignKey
     * @param string $modelTablePrimary
     *
     * @return bool
     */
    private function isOneToMany($primaryKey, $foreignKey, $modelTablePrimary)
    {
        if ($foreignKey->foreignField == $modelTablePrimary) {
            if ($foreignKey->localField != $primaryKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect many to one relationship on model table
     * If foreign key of model table is primary key of foreign table.
     *
     * @param GeneratorTable[] $tables
     * @param GeneratorTable $modelTable
     *
     * @return array
     */
    private function detectManyToOne($tables, $modelTable)
    {
        $manyToOneRelations = [];

        $foreignKeys = $modelTable->foreignKeys;

        foreach ($foreignKeys as $foreignKey) {
            $foreignTable = $foreignKey->foreignTable;
            $foreignField = $foreignKey->foreignField;

            if (!isset($tables[$foreignTable])) {
                continue;
            }

            if ($foreignField == $tables[$foreignTable]->primaryKey) {
                $additionalParams = [];
                if (!empty($foreignKey->localField)) {
                    $additionalParams = [
                        'foreignKey' => $foreignKey->localField,
                        'ownerKey' => $foreignField
                    ];
                }

                $modelName = model_name_from_table_name($foreignTable);
                $manyToOneRelations[] = GeneratorFieldRelation::parseRelation('mt1,' . $modelName, $additionalParams);
            }
        }

        return $manyToOneRelations;
    }
}
