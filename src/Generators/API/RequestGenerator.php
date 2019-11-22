<?php

namespace Thomisticus\Generator\Generators\API;

use Illuminate\Support\Str;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Utils\Database\Table;
use Thomisticus\Generator\Utils\FileUtil;

class RequestGenerator extends BaseGenerator
{
    /**
     * @var CommandData
     */
    private $commandData;

    /**
     * Request file path
     * @var string
     */
    private $path;

    /**
     * Request file name
     * @var string
     */
    private $fileName;

    /**
     * RequestGenerator constructor.
     *
     * @param \Thomisticus\Generator\Utils\CommandData $commandData
     */
    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->paths['request'];
        $this->fileName = $this->commandData->modelName . 'Request.php';
    }

    /**
     * Generates the Request file
     */
    public function generate()
    {
        $templateData = get_template('api.requests.request', 'app-generator');
        $this->commandData->addDynamicVariable('$RULES$', $this->generateRules());
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandObj->comment("\nRequest created: ");
        $this->commandData->commandObj->info($this->fileName);
    }

    /**
     * Generates the validation rules
     * @return array
     */
    private function generateRules()
    {
        $rules = [];
        if ($this->commandData->getOption('fromTable')) {
            $rules = $this->generateRulesFromTable();
        }

        if (empty($rules)) {
            $notRequiredFields = config('app-generator.options.hidden_fields', [])
                + config('app-generator.options.excluded_fields', []);

            foreach ($this->commandData->fields as $field) {
                if (
                    !$field->isPrimary && $field->isNotNull && empty($field->validations)
                    && !in_array($field->name, $notRequiredFields)
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

        return implode(',' . generate_new_line_tab(1, 3), $rules);
    }

    /**
     * Generate validation rules when the command '--fromTable' is present
     * @return array
     */
    private function generateRulesFromTable()
    {
        $rules = [];
        $timestamps = Table::getTimestampFieldNames();

        foreach ($this->commandData->fields as $field) {
            if (in_array($field->name, $timestamps) || !$field->isFillable) {
                continue;
            }

            $rule = [];

            if ($field->isNotNull) {
                $rule[] = 'required';
            }

            if ($field->isUnique) {
                $rule[] = 'unique';
            }

            switch ($field->fieldType) {
                case 'integer':
                    $rule[] = 'integer';
                    break;
                case 'decimal':
                case 'double':
                case 'float':
                    $rule[] = 'numeric';
                    break;
                case 'boolean':
                    $rule[] = 'boolean';
                    break;
                case 'dateTime':
                case 'dateTimeTz':
                    $rule[] = 'datetime';
                    break;
                case 'date':
                    $rule[] = 'date';
                    break;
                case 'enum':
                case 'string':
                case 'char':
                case 'text':
                    $rule[] = 'max:' . ($field->length ?? '45');
                    break;
                default:
                    $rule = [];
                    break;
            }

            if (!empty($rule)) {
                $rule = "'" . $field->name . "' => '" . implode('|', $rule) . "'";
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    /**
     * Rollback file creation
     */
    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandObj->comment('Create API Request file deleted: ' . $this->fileName);
        }
    }
}
