<?php

namespace Thomisticus\Generator\Generators;

use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Utils\FileUtil;
use Thomisticus\Generator\Utils\GeneratorFieldsInputUtil;

class TestTraitGenerator extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;

    /** @var string */
    private $fileName;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->pathApiTestTraits;
        $this->fileName = 'Make' . $this->commandData->modelName . 'Trait.php';
    }

    public function generate()
    {
        $templateData = get_template('test.trait', 'crud-generator');

        $templateData = $this->fillTemplate($templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandObj->comment("\nTestTrait created: ");
        $this->commandData->commandObj->info($this->fileName);
    }

    private function fillTemplate($templateData)
    {
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        $templateData = str_replace('$FIELDS$', implode(',' . generate_new_line_tab(1, 3), $this->generateFields()),
            $templateData);

        return $templateData;
    }

    private function generateFields()
    {
        $fields = [];

        foreach ($this->commandData->fields as $field) {
            if ($field->isPrimary) {
                continue;
            }

            $fieldData = "'" . $field->name . "' => " . '$fake->';

            switch ($field->fieldType) {
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
                    $fakerData = "date('Y-m-d H:i:s')";
                    break;
                case 'enum':
                    $fakerData = 'randomElement(' .
                        GeneratorFieldsInputUtil::prepareValuesArrayStr($field->htmlValues) .
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

    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandComment('Test trait file deleted: ' . $this->fileName);
        }
    }
}
