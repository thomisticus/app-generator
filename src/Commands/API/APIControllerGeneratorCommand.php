<?php

namespace Thomisticus\Generator\Commands\API;

use Thomisticus\Generator\Commands\BaseCommand;
use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Generators\API\APIControllerGenerator;

class APIControllerGeneratorCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'thomisticus.api:controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an api controller command';

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        $controllerGenerator = new APIControllerGenerator($this->commandData);
        $controllerGenerator->generate();

        $this->performPostActions();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    public function getOptions()
    {
        return array_merge(parent::getOptions(), []);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array_merge(parent::getArguments(), []);
    }
}
