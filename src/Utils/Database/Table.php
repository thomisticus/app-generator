<?php

namespace Thomisticus\Generator\Utils\Database;

use DB;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;

class Table
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
     * @var \Thomisticus\Generator\Utils\Database\Field[]
     */
    public $fields;

    /**
     * Table relationships
     * @var Relationship[]
     */
    public $relations;

    /**
     * Fields to be ignored
     * @var array
     */
    public $ignoredFields;

    /**
     * Table constructor.
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

        $this->prepareColumns();
        $this->primaryKey = static::getPrimaryKeyOfTable($tableName);
        $this->timestamps = static::getTimestampFieldNames();
        $this->defaultSearchable = config('app-generator.options.tables_searchable_default', false);
    }

    /**
     * Sets $columns property checking if a specific column is of single unique value
     */
    private function prepareColumns()
    {
        $tableColumns = $this->schemaManager->listTableColumns($this->tableName);
        $singleUniqueIndexedColumns = $this->getSingleUniqueIndexedColumnsFromTable($this->tableName);

        $this->columns = [];
        foreach ($tableColumns as $column) {
            $columnName = $column->getName();
            if (!in_array($columnName, $this->ignoredFields)) {
                $column->isUnique = in_array($columnName, $singleUniqueIndexedColumns);
                $this->columns[] = $column;
            }
        }
    }

    /**
     * Prepares array of Field from table columns.
     */
    public function prepareFieldsFromTable()
    {
        foreach ($this->columns as $column) {
            $type = $column->getType()->getName();

            /** @var array $fieldTypeMaps 'column type' => ['db type', 'html type'] */
            $fieldTypeMaps = [
                'integer' => ['integer'],
                'smallint' => ['smallInteger'],
                'bigint' => ['bigInteger'],
                'boolean' => ['boolean', 'checkbox,1'],
                'datetime' => ['datetime', 'date'],
                'datetimetz' => ['dateTimeTz', 'date'],
                'date' => ['date', 'date'],
                'time' => ['time', 'text'],
                'decimal' => ['decimal'],
                'float' => ['float'],
                'text' => ['text', 'textarea'],
                'string' => ['string', 'text']
            ];

            $type = isset($fieldTypeMaps[$type]) ? $type : 'string';

            $field = null;
            if (in_array($type, ['integer', 'smallint', 'bigint'])) {
                $field = $this->generateIntFieldInput($column, $fieldTypeMaps[$type][0]);
            }

            if (in_array($type, ['decimal', 'float'])) {
                $field = $this->generateNumberInput($column, $fieldTypeMaps[$type][0]);
            }

            if (empty($field)) {
                $field = $this->generateField($column, $fieldTypeMaps[$type][0], $fieldTypeMaps[$type][1]);
            }

            $this->fields[] = $this->prepareAdditionalFieldPropertiesFromTable($field, $column);
        }

        return $this;
    }

    /**
     * Sets additional properties for the field when being prepared from a table
     *
     * @param Field $field
     * @param Column $column
     * @return Field
     */
    private function prepareAdditionalFieldPropertiesFromTable(Field $field, Column $column)
    {
        $fieldName = strtolower($field->name);

        if (in_array($fieldName, ['email', 'password'])) {
            $field->htmlType = $fieldName;
        } elseif (in_array($fieldName, array_map('strtolower', $this->timestamps))) {
            $field->isSearchable = $field->isFillable = $field->inForm = $field->inIndex = $field->inView = false;
        }

        $field->isNotNull = (bool)$column->getNotNull();
        $field->description = $column->getComment(); // get comments from table

        return $field;
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
     * Check if key is primary key and sets field options.
     *
     * @param \Thomisticus\Generator\Utils\Database\Field $field
     *
     * @return \Thomisticus\Generator\Utils\Database\Field
     */
    private function checkForPrimary(Field $field)
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
     * @return \Thomisticus\Generator\Utils\Database\Field
     */
    private function generateField($column, $dbType, $htmlType)
    {
        $field = new Field();
        $field->name = $column->getName();
        $field->parseDBType($dbType, $column);
        $field->parseHtmlInput($htmlType);
        $field->isUnique = $column->isUnique ?? false;

        return $this->checkForPrimary($field);
    }

    /**
     * Generates number field.
     *
     * @param \Doctrine\DBAL\Schema\Column $column
     * @param string $dbType
     *
     * @return \Thomisticus\Generator\Utils\Database\Field
     */
    private function generateNumberInput($column, $dbType)
    {
        $field = new Field();
        $field->name = $column->getName();
        $field->parseDBType($dbType . ',' . $column->getPrecision() . ',' . $column->getScale());
        $field->htmlType = 'number';

        return $this->checkForPrimary($field);
    }

    /**
     * Generates integer text field for database.
     *
     * @param string $dbType
     * @param Column $column
     *
     * @return \Thomisticus\Generator\Utils\Database\Field
     */
    private function generateIntFieldInput($column, $dbType)
    {
        $field = new Field();
        $field->name = $column->getName();
        $field->parseDBType($dbType);
        $field->htmlType = 'number';

        $field->dbInput .= $column->getAutoincrement() ? ',true' : ',false';

        if ($column->getUnsigned()) {
            $field->dbInput .= ',true';
        }

        $field->isUnique = $column->isUnique ?? false;

        return $this->checkForPrimary($field);
    }

    /**
     * Prepares relations (FieldRelation) array from table foreign keys.
     * @return $this
     */
    public function prepareRelations()
    {
        $tablesToCheckForRelations = $this->prepareForeignKeys();
        $this->checkForRelations($tablesToCheckForRelations);

        return $this;
    }

    /**
     * Prepares foreign keys from table with required details.
     * It will go through all database tables.
     *
     * @return array
     */
    public function prepareForeignKeys()
    {
        $tables = $this->schemaManager->listTables();

        $tablesToSearchForeignKeys = [];

        foreach ($tables as $table) {
            if ($primaryKey = $table->getPrimaryKey()) {
                $primaryKey = $primaryKey->getColumns()[0];
            }
            $foreignKeys = [];
            $tableForeignKeys = $table->getForeignKeys();
            foreach ($tableForeignKeys as $foreignKey) {
                $foreignKey = [
                    'ownerTableName' => $table->getName(),
                    'name' => $foreignKey->getName(),
                    'localField' => $foreignKey->getLocalColumns()[0],
                    'foreignField' => $foreignKey->getForeignColumns()[0],
                    'foreignTable' => $foreignKey->getForeignTableName(),
                    'onUpdate' => $foreignKey->onUpdate(),
                    'onDelete' => $foreignKey->onDelete(),
                ];

                $foreignKeys[] = new ForeignKey(...array_values($foreignKey));
            }

            $tablesToSearchForeignKeys[$table->getName()] = compact('primaryKey', 'foreignKeys');
        }

        return $tablesToSearchForeignKeys;
    }

    /**
     * Prepares relations array from table foreign keys.
     *
     * @param array $tables Array of tables with primary key and foreign keys
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
            $foreignKeys = $table['foreignKeys'];
            $primary = $table['primaryKey'];

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
                    if ($this->isOneToOne($primary, $foreignKey, $modelTable['primaryKey'])) {
                        $additionalParams = [];
                        if (!empty($foreignKey->localField) && !empty($foreignKey->foreignField)) {
                            $additionalParams = [
                                'foreignKey' => $foreignKey->localField,
                                'localKey' => $foreignKey->foreignField
                            ];
                        }

                        $modelName = model_name_from_table_name($tableName);
                        $this->relations[] = Relationship::parseRelation('1t1,' . $modelName, $additionalParams);
                        continue;
                    }

                    // detect if one to many relationship is there
                    if ($this->isOneToMany($primary, $foreignKey, $modelTable['primaryKey'])) {
                        $additionalParams = [];
                        if (!empty($foreignKey->localField) && !empty($foreignKey->foreignField)) {
                            $additionalParams = [
                                'foreignKey' => $foreignKey->localField,
                                'localKey' => $foreignKey->foreignField
                            ];
                        }

                        $modelName = model_name_from_table_name($tableName);
                        $this->relations[] = Relationship::parseRelation('1tm,' . $modelName, $additionalParams);
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
     * @param Table[] $tables
     * @param string $tableName
     * @param Table $modelTable
     * @param string $modelTableName
     *
     * @return bool|\Thomisticus\Generator\Utils\Database\Relationship
     */
    private function isManyToMany($tables, $tableName, $modelTable, $modelTableName)
    {
        // Get table details
        $table = $tables[$tableName];

        // Many to many model table name
        $manyToManyTable = '';

        $foreignKeys = $table['foreignKeys'];
        $primary = $table['primaryKey'];

        // Check if any foreign key is there from model table
        if (!in_array($modelTableName, array_column($foreignKeys, 'foreignTable'))) {
            return false;
        }

        $additionalParams = [];

        // If foreign key is there
        foreach ($foreignKeys as $foreignKey) {
            $foreignField = $foreignKey->foreignField; // cd_fabrica_software
            $foreignTableName = $foreignKey->foreignTable; // tb_fabrica_software

            // If foreign table is model table
            if ($foreignTableName == $modelTableName) {
                $foreignTable = $modelTable;
            } else {
                $foreignTable = $tables[$foreignTableName];
                // Get the many to many model table name
                $manyToManyTable = $foreignTableName;
            }

            if ($foreignKey->foreignField == $this->primaryKey && $foreignKey->foreignTable == $modelTableName) {
                $additionalParams['foreignPivotKey'] = $foreignKey->localField;
            } else {
                $additionalParams['relatedPivotKey'] = $foreignKey->localField;
            }

            // If foreign field is not primary key of foreign table then it can not be many to many
            // Or if foreign field is primary key of this table then it can not be many to many
            if ($foreignField != $foreignTable['primaryKey'] || $foreignField == $primary) {
                return false;
            }
        }

        $modelName = model_name_from_table_name($manyToManyTable);

        return Relationship::parseRelation('mtm,' . $modelName . ',' . $tableName, $additionalParams);
    }

    /**
     * Detects if one to one relationship is there
     * If foreign key of table is primary key of foreign table
     * Also foreign key field is primary key of this table.
     *
     * @param string $primaryKey
     * @param \Thomisticus\Generator\Utils\Database\ForeignKey $foreignKey
     * @param string $modelTablePrimary
     *
     * @return bool
     */
    private function isOneToOne($primaryKey, $foreignKey, $modelTablePrimary)
    {
        if ($foreignKey->foreignField == $modelTablePrimary) {
            if ($foreignKey->localField == $primaryKey) {
                return true;
            } else {
                $uniqueColumns = $this->getSingleUniqueIndexedColumnsFromTable($foreignKey->ownerTableName);
                return in_array($foreignKey->localField, $uniqueColumns);
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
     * @param \Thomisticus\Generator\Utils\Database\ForeignKey $foreignKey
     * @param string $modelTablePrimary
     *
     * @return bool
     */
    private function isOneToMany($primaryKey, $foreignKey, $modelTablePrimary)
    {
        return $foreignKey->foreignField == $modelTablePrimary && $foreignKey->localField != $primaryKey;
    }

    /**
     * Detect many to one relationship on model table
     * If foreign key of model table is primary key of foreign table.
     *
     * @param Table[] $tables
     * @param Table $modelTable
     *
     * @return array
     */
    private function detectManyToOne($tables, $modelTable)
    {
        $manyToOneRelations = [];

        $foreignKeys = $modelTable['foreignKeys'];

        foreach ($foreignKeys as $foreignKey) {
            $foreignTable = $foreignKey->foreignTable;
            $foreignField = $foreignKey->foreignField;

            if (isset($tables[$foreignTable]) && $foreignField == $tables[$foreignTable]['primaryKey']) {
                $additionalParams = [];
                if (!empty($foreignKey->localField)) {
                    $additionalParams = [
                        'foreignKey' => $foreignKey->localField,
                        'ownerKey' => $foreignField
                    ];
                }

                $modelName = model_name_from_table_name($foreignTable);
                $manyToOneRelations[] = Relationship::parseRelation('mt1,' . $modelName, $additionalParams);
            }
        }

        return $manyToOneRelations;
    }

    /**
     * Retrieves an array of column names that contain a single unique index.
     * (Unique indexes of only one column.)
     *
     * @param string $tableName
     * @return array
     */
    public function getSingleUniqueIndexedColumnsFromTable($tableName)
    {
        $indexes = $this->schemaManager->listTableIndexes($tableName);

        $uniqueIndexedColumns = [];
        foreach ($indexes as $index) {
            $indexedColumns = $index->getColumns();
            if ($index->isUnique() && count($indexedColumns) == 1) {
                $uniqueIndexedColumns[] = reset($indexedColumns);
            }
        }

        return array_unique($uniqueIndexedColumns);
    }
}
