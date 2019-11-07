<?php

namespace Thomisticus\Generator\Generators\API;

use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Generators\Common\ModelGenerator;
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
     * @param CommandData $commandData
     */
    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->paths['api_request'];
        $this->fileName = $this->commandData->modelName . 'APIRequest.php';
    }

    /**
     * Generates the Request file
     */
    public function generate()
    {
        $templateData = get_template('api.request.request', 'app-generator');
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandObj->comment("\nRequest created: ");
        $this->commandData->commandObj->info($this->fileName);
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
