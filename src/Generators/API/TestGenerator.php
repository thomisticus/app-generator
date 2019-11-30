<?php

namespace Thomisticus\Generator\Generators\API;

use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\FileUtil;

class TestGenerator extends BaseGenerator
{
    /**
     * @var CommandData
     */
    private $commandData;

    /**
     * Api test file path
     * @var string
     */
    private $path;

    /**
     * Api test file name
     * @var string
     */
    private $fileName;

    /**
     * TestGenerator constructor.
     * @param CommandData $commandData
     */
    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->paths['api_tests'];
        $this->fileName = $this->commandData->modelName . 'ApiTest.php';
    }

    /**
     * Generates the Api test file
     */
    public function generate()
    {
        $templateData = get_template('api.tests.api_test', 'app-generator');
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandObj->comment("\nApiTest created: ");
        $this->commandData->commandObj->info($this->fileName);
    }

    /**
     * Rollback the test file creation
     */
    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandObj->comment('API Test file deleted: ' . $this->fileName);
        }
    }
}
