<?php

namespace Thomisticus\Generator\Generators\API;

use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\FileUtil;

class APIControllerGenerator extends BaseGenerator
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
        $this->path = $commandData->config->paths['api_controller'];
        $this->fileName = $this->commandData->modelName . 'APIController.php';
    }

    public function generate()
    {
        if ($this->commandData->getOption('repositoryPattern')) {
            $templateName = 'api_controller';
        } else {
            $templateName = 'model_api_controller';
        }

        $templateData = get_template("api.controller.$templateName", 'app-generator');

//        $paginate = $this->commandData->getOption('paginate');
//
//        if ($paginate) {
//            $templateData = str_replace('$RENDER_TYPE$', 'paginate(' . $paginate . ')', $templateData);
//        } else {
//            $templateData = str_replace('$RENDER_TYPE$', 'all()', $templateData);
//        }

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);
        $templateData = $this->fillDocs($templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandObj->comment("\nAPI Controller created: ");
        $this->commandData->commandObj->info($this->fileName);
    }

    private function fillDocs($templateData)
    {
        $methods = ['controller', 'index', 'store', 'show', 'update', 'destroy'];

        $templatePrefix = 'api.docs.controller';
        $templateType = 'app-generator';

        foreach ($methods as $method) {
            $key = '$DOC_' . strtoupper($method) . '$';
            $docTemplate = get_template($templatePrefix . '.' . $method, $templateType);
            $docTemplate = fill_template($this->commandData->dynamicVars, $docTemplate);
            $templateData = str_replace($key, $docTemplate, $templateData);
        }

        return $templateData;
    }

    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandObj->comment('API Controller file deleted: ' . $this->fileName);
        }
    }
}
