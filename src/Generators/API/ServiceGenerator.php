<?php

namespace Thomisticus\Generator\Generators\API;

use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\FileUtil;

class ServiceGenerator extends BaseGenerator
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
        $this->path = $commandData->config->pathService;
        $this->fileName = $this->commandData->modelName . 'Service.php';
    }

    public function generate()
    {
        $templateName = $this->commandData->getOption('jsonResponse') ? 'service_json_response' : 'service';
        $templateData = get_template('services.' . $templateName, 'app-generator');

        $paginate = $this->commandData->getOption('paginate');

        if ($paginate) {
            $templateData = str_replace('$RENDER_TYPE$', 'paginate(' . $paginate . ')', $templateData);
        } else {
            $templateData = str_replace('$RENDER_TYPE$', 'all()', $templateData);
        }

        $relationships = [];
        foreach ($this->commandData->relations as $relation) {
            $attributes = $relation->getRelationAttributes($relation->inputs[0]);

            if (!empty($attributes['functionName'])) {
                $relationships[] = "'" . $attributes['functionName'] . "'";
            }
        }

        $templateData = str_replace(
            '$RELATIONSHIPS$',
            implode(',' . generate_new_line_tab(1, 2), $relationships),
            $templateData
        );

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandComment("\nService created: ");
        $this->commandData->commandInfo($this->fileName);
    }

    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandComment('Service file deleted: ' . $this->fileName);
        }
    }
}
