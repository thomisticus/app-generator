<?php

namespace Thomisticus\Generator\Utils;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Thomisticus\Generator\Utils\GeneratorConfig;
use Thomisticus\Generator\Utils\Database\Field;
use Thomisticus\Generator\Utils\Database\Relationship;
use Thomisticus\Generator\Utils\FieldsInputUtil;
use Thomisticus\Generator\Utils\Database\Table;

class CommandData
{
    public static $COMMAND_TYPE_API = 'api';

    /**
     * @var string Model name
     */
    public $modelName;

    /**
     * @var string
     */
    public $commandType;

    /**
     * @var GeneratorConfig
     */
    public $config;

    /**
     * Primary key name added on command
     * @var string|
     */
    public $primaryKey;

    /**
     * @var \Thomisticus\Generator\Utils\Database\Field[]
     */
    public $fields = [];

    /**
     * @var Relationship[]
     */
    public $relations = [];

    /**
     * @var Command Laravel Command object
     */
    public $commandObj;

    /**
     * @var array Dynamic variables that will be replaced in the template (stub) file
     */
    public $dynamicVars = [];

    /**
     * CommandData constructor.
     * @param Command $commandObj
     * @param $commandType
     */
    public function __construct(Command $commandObj, $commandType)
    {
        $this->commandObj = $commandObj;
        $this->commandType = $commandType;
        $this->config = new GeneratorConfig();
    }

    /**
     * Inits the GeneratorConfig to prepare all properties and load the paths to replace in the final file
     *
     * @return $this
     */
    public function initCommandData()
    {
        $this->config->init($this);
        return $this;
    }

    /**
     * Sets the $fields and $relations properties accordingly to the type of input that may be from fields in a file,
     * direct json from gui interface, from existing table or direct console's inputs
     */
    public function setFieldsAndRelations()
    {
        $this->fields = [];

        if ($fieldsFileValue = $this->getOption('fieldsFile')) {
            return $this->getInputFromFile($fieldsFileValue);
        }

        if ($fileContents = $this->getOption('jsonFromGUI')) {
            return $this->getInputFromJsonGUI($fileContents);
        }

        if ($this->getOption('fromTable')) {
            return $this->getInputFromTable();
        }

        return $this->getInputFromConsole();
    }

    /**
     * If you have schema files stored then it can be used with generator rather than entering schema from the console.
     * You can find a sample file at app-generator\samples\fields_sample.json
     * To use schema file, use --fieldsFile option.
     *
     * @param string $fieldsFileValue
     */
    private function getInputFromFile($fieldsFileValue)
    {
        try {
            if (file_exists($fieldsFileValue)) {
                $filePath = $fieldsFileValue;
            } elseif (file_exists(base_path($fieldsFileValue))) {
                $filePath = base_path($fieldsFileValue);
            } else {
                $filePath = config('app-generator.path.schema_files') . $fieldsFileValue;
            }

            if (!file_exists($filePath)) {
                $this->commandObj->error('File with the fields not found');
                exit;
            }

            $jsonData = json_decode(file_get_contents($filePath), true);

            $this->treatInputFields($jsonData);
        } catch (Exception $e) {
            $this->commandObj->error($e->getMessage());
            exit;
        }
    }


    /**
     * Direct Json string while using GUI interface.
     * Eg: --jsonFromGUI={}
     *
     * @param string $fileContents
     */
    private function getInputFromJsonGUI($fileContents)
    {
        try {
            $jsonData = json_decode($fileContents, true);

            // Override config options from jsonFromGUI
            $this->config->overrideOptionsFromJsonFile($jsonData);

            // Manage custom table name option
            if (isset($jsonData['tableName'])) {
                $tableName = $jsonData['tableName'];
                $this->config->tableName = $tableName;
                $this->addDynamicVariable([
                    '$TABLE_NAME$' => $tableName,
                    '$TABLE_NAME_TITLE$' => Str::studly($tableName)
                ]);
            }

            // Manage migrate option
            if (isset($jsonData['migrate']) && !$jsonData['migrate']) {
                $this->config->options['skip'][] = 'migration';
            }

            $this->treatInputFields($jsonData['fields']);
        } catch (Exception $e) {
            $this->commandObj->error($e->getMessage());
            exit;
        }
    }

    /**
     * Get input fields and relations from existing table. To use it: --fromTable option.
     */
    private function getInputFromTable()
    {
        $ignoredFields = $this->getOption('ignoreFields');
        $ignoredFields = !empty($ignoredFields) ? explode(',', trim($ignoredFields)) : [];

        $tableName = $this->dynamicVars['$TABLE_NAME$'];
        $table = (new Table($tableName, $ignoredFields))
            ->prepareFieldsFromTable()
            ->prepareRelations();

        $this->primaryKey = $table->primaryKey;
        $this->fields = $table->fields;
        $this->relations = $table->relations;
    }

    /**
     * Get fields and relations directly from console inputs (id and timestamps are added automatically)
     * This case is called when any of the other parameters are sent.
     */
    private function getInputFromConsole()
    {
        $this->commandObj
            ->info('Specify fields for the model (skip id & timestamp fields, we will add it automatically)');
        $this->commandObj->info('Read docs carefully to specify field inputs)');
        $this->commandObj->info('Enter "exit" to finish');

        $this->addPrimaryKey();

        while (true) {
            $fieldInputStr = $this->commandObj->ask('Field: (name db_type html_type options)', '');

            if (empty($fieldInputStr) || $fieldInputStr == 'exit') {
                break;
            }

            if (!FieldsInputUtil::validateFieldInput($fieldInputStr)) {
                $this->commandObj->error('Invalid Input. Try again');
                continue;
            }

            $validations = $this->commandObj->ask('Enter validations: ', false);
            $validations = $validations ?? '';

            $relation = '';
            if ($this->getOption('relations')) {
                $relation = $this->commandObj->ask('Enter relationship (Leave Blank to skip):', false);
            }

            $this->fields[] = FieldsInputUtil::processFieldInput($fieldInputStr, $validations);

            if (!empty($relation)) {
                $this->relations[] = Relationship::parseRelation($relation);
            }
        }

        if (config('app-generator.timestamps.enabled', true)) {
            $this->addTimestamps();
        }
    }

    /**
     * Adds the primary key field before start getting the inputs from console
     */
    private function addPrimaryKey()
    {
        $primaryKey = new Field();

        $primaryKey->name = 'id';
        if ($primary = $this->getOption('primary')) {
            $primaryKey->name = $primary;
        }

        $primaryKey->parseDBType('increments')->parseOptions('searchable,fillable,primary,inForm,inIndex');

        $this->fields[] = $primaryKey;
    }

    /**
     * Adds the timestamp fields after getting the inputs from console
     */
    private function addTimestamps()
    {
        $timestamps = ['created_at', 'updated_at'];
        if ($this->getOption('softDelete')) {
            $timestamps[] = 'deleted_at';
        }

        foreach ($timestamps as $timestampName) {
            $field = new Field();
            $field->name = config('app-generator.timestamps.' . $timestampName, $timestampName);
            $field->parseDBType('timestamp')->parseOptions('searchable,fillable,inForm,inIndex');
            $this->fields[] = $field;
        }
    }

    /**
     * Treats input fields when got from file or json, filling $relations and $fields properties
     * @param array $data
     */
    private function treatInputFields($data)
    {
        foreach ($data as $field) {
            if (isset($field['type']) && $field['relation']) {
                $this->relations[] = Relationship::parseRelation($field['relation']);
            } else {
                $this->fields[] = Field::parseFieldFromFile($field);
                if (isset($field['relation'])) {
                    $this->relations[] = Relationship::parseRelation($field['relation']);
                }
            }
        }
    }

    /**
     * Retrieves an option value from GeneratorConfig
     * (that basically fills its options with the the app-generator config file)
     *
     * @param string $option
     * @return bool|mixed
     */
    public function getOption($option)
    {
        return $this->config->getOption($option);
    }

    /**
     * Retrieves the value of an addon (if its activated or not)
     *
     * @param string $option
     * @return bool|mixed
     */
    public function getAddOn($option)
    {
        return $this->config->getAddOn($option);
    }

    /**
     * Sets a dynamic variables and their values that will be used to replace in the template (stub) file
     *
     * @param array|string $nameOrArray
     * @param string|null $val
     */
    public function addDynamicVariable($nameOrArray, $val = null)
    {
        if (is_array($nameOrArray)) {
            foreach ($nameOrArray as $name => $val) {
                $this->dynamicVars[$name] = $val;
            }
        } else {
            $this->dynamicVars[$nameOrArray] = $val;
        }
    }
}
