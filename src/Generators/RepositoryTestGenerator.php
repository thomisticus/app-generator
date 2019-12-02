<?php

namespace Thomisticus\Generator\Generators;

use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Utils\FileUtil;

class RepositoryTestGenerator extends BaseGenerator
{
    /**
     * @var CommandData
     */
    private $commandData;

    /**
     * Repository test file path
     * @var string
     */
    private $path;

    /**
     * Repository test file name
     * @var string
     */
    private $fileName;

    /**
     * RepositoryTestGenerator constructor.
     * @param $commandData
     */
    public function __construct($commandData)
    {
        $this->commandData = $commandData;
        $this->path = config('app-generator.path.repository_test', base_path('tests/Repositories/'));
        $this->fileName = $this->commandData->modelName . 'RepositoryTest.php';
    }

    /**
     * Generates the repository test file
     */
    public function generate()
    {
        $templateData = get_template('tests.repository_test', 'app-generator');
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandObj->line("- RepositoryTest created: <info>{$this->fileName}</info>");
    }

    /**
     * Rollback file creation
     */
    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandObj->line("- Repository Test file deleted: <info>{$this->fileName}</info>");
        }
    }
}
