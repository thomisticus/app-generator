<?php

namespace Thomisticus\Generator\Generators\Common;

use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\FileUtil;
use Thomisticus\Generator\Utils\GeneratorFieldsInputUtil;

/**
 * Class FactoryGenerator.
 */
class FactoryGenerator extends BaseGenerator
{
    /**
     * @var \Thomisticus\Generator\Utils\CommandData
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
     * @param \Thomisticus\Generator\Utils\CommandData $commandData
     */
    public function __construct(\Thomisticus\Generator\Utils\CommandData $commandData)
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

        foreach ($this->commandData->fields as $field) {
            if ($field->isPrimary) {
                continue;
            }

            $fieldData = "'" . $field->name . "' => " . '$faker->';

            switch (strtolower($field->fieldType)) {
                case 'integer':
                case 'float':
                    $fakerData = 'randomDigitNotNull';
                    break;
                case 'string':
                    $fakerData = 'word';
                    break;
                case 'text':
                    $fakerData = 'text';
                    break;
                case 'datetime':
                case 'timestamp':
                    $fakerData = "date('Y-m-d H:i:s')";
                    break;
                case 'enum':
                    $fakerData = 'randomElement(' .
                        GeneratorFieldsInputUtil::prepareValuesArrayString($field->htmlValues) .
                        ')';
                    break;
                default:
                    $fakerData = 'word';
            }

            $fieldData .= $fakerData;

            $fields[] = $fieldData;
        }

        return $fields;
    }
}
