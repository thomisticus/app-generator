<?php

namespace Thomisticus\Generator\Commands\VueJs;

use Thomisticus\Generator\Commands\BaseCommand;
use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Generators\MigrationGenerator;
use Thomisticus\Generator\Generators\ModelGenerator;
use Thomisticus\Generator\Generators\RepositoryGenerator;
use Thomisticus\Generator\Generators\Scaffold\MenuGenerator;
use Thomisticus\Generator\Generators\VueJs\APIRequestGenerator;
use Thomisticus\Generator\Generators\VueJs\ControllerGenerator;
use Thomisticus\Generator\Generators\VueJs\ModelJsConfigGenerator;
use Thomisticus\Generator\Generators\VueJs\RoutesGenerator;
use Thomisticus\Generator\Generators\VueJs\ViewGenerator;

class VueJsGeneratorCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'thomisticus:vuejs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a full CRUD views and config using VueJs Framework for given model';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->commandData = new CommandData($this, CommandData::$COMMAND_TYPE_VUEJS);
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        if (!$this->commandData->getOption('fromTable')) {
            $migrationGenerator = new MigrationGenerator($this->commandData);
            $migrationGenerator->generate();
        }

        $modelGenerator = new ModelGenerator($this->commandData);
        $modelGenerator->generate();

        $repositoryGenerator = new RepositoryGenerator($this->commandData);
        $repositoryGenerator->generate();

        $requestGenerator = new APIRequestGenerator($this->commandData);
        $requestGenerator->generate();

        $controllerGenerator = new ControllerGenerator($this->commandData);
        $controllerGenerator->generate();

        $modelJsConfigGenerator = new ModelJsConfigGenerator($this->commandData);
        $modelJsConfigGenerator->generate();

        $routeGenerator = new RoutesGenerator($this->commandData);
        $routeGenerator->generate();

        $viewGenerator = new ViewGenerator($this->commandData);
        $viewGenerator->generate();

        if ($this->commandData->config->getAddOn('menu.enabled')) {
            $menuGenerator = new MenuGenerator($this->commandData);
            $menuGenerator->generate();
        }

        $this->performPostActionsWithMigration();
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
