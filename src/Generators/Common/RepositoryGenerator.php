<?php

namespace Thomisticus\Generator\Generators\Common;

use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\FileUtil;

class RepositoryGenerator extends BaseGenerator
{
    /**
     * @var CommandData
     */
    private $commandData;

    /**
     * Repository file path
     * @var string
     */
    private $path;

    /**
     * Repository file name
     * @var string
     */
    private $fileName;


    /**
     * RepositoryGenerator constructor.
     * @param CommandData $commandData
     */
    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->paths['repository'];
        $this->fileName = $this->commandData->modelName . 'Repository.php';
    }

    /**
     * Generates the repository file
     */
    public function generate()
    {
        $templateData = get_template('repository', 'app-generator');
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        $searchables = [];
        foreach ($this->commandData->fields as $field) {
            if ($field->isSearchable) {
                $searchables[] = "'" . strtolower($field->name) . "'";
            }
        }

        $templateData = str_replace(
            '$FIELDS$',
            implode(',' . generate_new_line_tab(1, 2), $searchables),
            $templateData
        );

        $docsTemplate = get_template('docs.repository', 'app-generator');
        $docsTemplate = fill_template($this->commandData->dynamicVars, $docsTemplate);

        $docsTemplate = str_replace('$GENERATE_DATE$', date('F j, Y, g:i a T'), $docsTemplate);
        $templateData = str_replace('$DOCS$', $docsTemplate, $templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandObj->comment("\nRepository created: ");
        $this->commandData->commandObj->info($this->fileName);
    }

    /**
     * Rollback the repository generation
     */
    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandObj->comment('Repository file deleted: ' . $this->fileName);
        }
    }
}
