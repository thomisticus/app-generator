<?php

namespace Thomisticus\Generator\Generators\Common;

use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Utils\Database\Table;
use Thomisticus\Generator\Utils\FileUtil;
use Thomisticus\Generator\Utils\FieldsInputUtil;

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

        $this->commandData->commandObj->comment("\nFactory created: ");
        $this->commandData->commandObj->info($this->fileName);
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

        foreach ($this->commandData->fields as $field) {
            if (in_array($field->name, $timestamps) || $field->isPrimary) {
                continue;
            }

            $fieldData = "'" . $field->name . "' => " . '$faker->';

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
                'timestamp' => "date('Y-m-d H:i:s')",
                'boolean' => 'boolean',
                'enum' => 'randomElement(' . FieldsInputUtil::prepareValuesArrayString($field->htmlValues) . ')'
            ];

            $fieldType = strtolower($field->fieldType);
            if (!isset($fieldTypeMap[$fieldType])) {
                $fieldType = 'string';
            }

            $fieldData .= $fieldTypeMap[$fieldType];

            $fields[] = $fieldData;
        }

        return $fields;
    }
}
