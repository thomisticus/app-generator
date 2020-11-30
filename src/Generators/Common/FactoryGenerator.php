<?php

namespace Thomisticus\Generator\Generators\Common;

use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Utils\Database\Table;
use Thomisticus\Generator\Utils\FieldsInputUtil;
use Thomisticus\Generator\Utils\FileUtil;

/**
 * Class FactoryGenerator.
 */
class FactoryGenerator extends BaseGenerator
{
    /**
     * @var CommandData
     */
    private $commandData;

    /**
     * Factory file path
     * @var string
     */
    private $path;

    /**
     * Factory file name
     * @var string
     */
    private $fileName;

    /**
     * FactoryGenerator constructor.
     *
     * @param CommandData $commandData
     */
    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->paths['factory'];
        $this->fileName = $this->commandData->modelName . 'Factory.php';
    }

    /**
     * Generates the factory file
     */
    public function generate()
    {
        $templateData = get_template('factories.model_factory', 'app-generator');
        $templateData = $this->fillTemplate($templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandObj->line("- Factory created: <info>{$this->fileName}</info>");
    }

    /**
     * Fills the factory template
     *
     * @param string $templateData
     *
     * @return mixed|string
     */
    private function fillTemplate($templateData)
    {
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        return str_replace(
            '$FIELDS$',
            implode(',' . generate_new_line_tab(1, 2), $this->generateFakerFields()),
            $templateData
        );
    }

    /**
     * Generate faker fields
     * @return array
     */
    private function generateFakerFields()
    {
        $fields = [];
        $timestamps = Table::getTimestampFieldNames();

        $fieldTypeMap = [
            'integer' => 'randomDigitNotNull',
            'biginteger' => 'randomDigitNotNull',
            'float' => 'randomFloat',
            'string' => 'word',
            'char' => 'randomLetter',
            'text' => 'text',
            'mediumtext' => 'text',
            'longtext' => 'text',
            'datetime' => "date('Y-m-d H:i:s')",
            'date' => "date('Y-m-d H:i:s')",
            'timestamp' => "date('Y-m-d H:i:s')",
            'boolean' => 'boolean',
        ];

        foreach ($this->commandData->fields as $field) {
            if (!in_array($field->name, $timestamps) && !$field->isPrimary) {
                $fieldData = "'" . $field->name . "' => " . '$faker->';

                $fieldType = strtolower($field->fieldType);

                if ($fieldType === 'enum') {
                    $fieldTypeMap['enum'] = 'randomElement(' . FieldsInputUtil::prepareValuesArrayString($field->htmlValues) . ')';
                }

                $fields[] = $fieldData . $fieldTypeMap[$fieldType] ?? $fieldTypeMap['string'];
            }
        }

        return $fields;
    }

    /**
     * Rollback the factory file creation
     */
    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandObj->line("- Factory file deleted: <info>{$this->fileName}</info>");
        }
    }
}
