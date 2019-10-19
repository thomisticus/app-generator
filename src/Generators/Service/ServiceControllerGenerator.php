<?php

namespace Thomisticus\Generator\Generators\Service;

use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\FileUtil;

class ServiceControllerGenerator extends BaseGenerator
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
        $this->path = $commandData->config->pathController;
        $this->fileName = $this->commandData->modelName . 'Controller.php';
    }

    public function generate()
    {
        $templateData = get_template('scaffold.controller.service_controller', 'crud-generator');

        $paginate = $this->commandData->getOption('paginate');

        if ($paginate) {
            $templateData = str_replace('$RENDER_TYPE$', 'paginate(' . $paginate . ')', $templateData);
        } else {
            $templateData = str_replace('$RENDER_TYPE$', 'all()', $templateData);
        }

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandComment("\nController created: ");
        $this->commandData->commandInfo($this->fileName);
    }

    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandComment('Controller file deleted: ' . $this->fileName);
        }
    }
}
