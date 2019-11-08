<?php

namespace Thomisticus\Generator\Generators\API;

use Illuminate\Support\Str;
use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Generators\BaseGenerator;

class RouteGenerator extends BaseGenerator
{
    /**
     * @var CommandData
     */
    private $commandData;

    /**
     * API routes file path
     * @var string
     */
    private $path;

    /**
     * Current content of routes/api.php file
     * @var false|string
     */
    private $routeContents;

    /**
     * Routes template content
     * @var string
     */
    private $routesTemplate;

    /**
     * RouteGenerator constructor.
     * @param CommandData $commandData
     */
    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->paths['api_routes'];

        $this->routeContents = file_get_contents($this->path);

        $templateName = !empty($this->commandData->config->prefixes['route']) ? 'prefix_routes' : 'routes';
        $routesTemplate = get_template('api.routes.' . $templateName, 'app-generator');

        $this->routesTemplate = fill_template($this->commandData->dynamicVars, $routesTemplate);
    }

    /**
     * Generates the routes in the route file
     */
    public function generate()
    {
        $this->routeContents .= "\n\n" . $this->routesTemplate;

        file_put_contents($this->path, $this->routeContents);

        $commentText = "\n" . $this->commandData->config->modelNames['camel_plural'] . ' api routes added.';
        $this->commandData->commandObj->comment($commentText);
    }

    /**
     * Rollback the routes in the api.php file
     */
    public function rollback()
    {
        if (Str::contains($this->routeContents, $this->routesTemplate)) {
            $this->routeContents = str_replace($this->routesTemplate, '', $this->routeContents);
            file_put_contents($this->path, $this->routeContents);
            $this->commandData->commandObj->comment('api routes deleted');
        }
    }
}
