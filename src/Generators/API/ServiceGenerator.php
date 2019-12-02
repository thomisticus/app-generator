<?php

namespace Thomisticus\Generator\Generators\API;

use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Utils\FileUtil;

class ServiceGenerator extends BaseGenerator
{
    /**
     * @var CommandData
     */
    private $commandData;

    /**
     * Service file path
     * @var string
     */
    private $path;

    /**
     * Service file name
     * @var string
     */
    private $fileName;

    /**
     * ServiceGenerator constructor.
     * @param CommandData $commandData
     */
    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->paths['service'];
        $this->fileName = $this->commandData->modelName . 'Service.php';
    }

    /**
     * Generates the service file
     */
    public function generate()
    {
        $templateData = get_template('api.services.service', 'app-generator');

        $paginate = $this->commandData->getOption('paginate');
        $renderType = $paginate ? 'paginate(' . $paginate . ')' : 'all()';

        $templateData = str_replace('$RENDER_TYPE$', $renderType, $templateData);

        $relationships = [];
        $count = 1;
        foreach ($this->commandData->relations as $relation) {
            $relation->commandData = $this->commandData;
            $relationText = $relation->parseRelationFunctionName($relation);

            if (in_array($relationText, $relationships)) {
                $relationText .= '_' . $count;
                $count++;
            }

            $attributes = $relation->getRelationAttributes($relationText, $this->commandData->modelName);

            if (!empty($attributes)) {
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

        $this->commandData->commandObj->comment("\nService created: ");
        $this->commandData->commandObj->info($this->fileName);
    }

    /**
     * Rollback file creation
     */
    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandObj->comment('Service file deleted: ' . $this->fileName);
        }
    }
}
