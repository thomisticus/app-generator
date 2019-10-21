<?php

namespace Thomisticus\Generator\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Generators\API\APIControllerGenerator;
use Thomisticus\Generator\Generators\API\APIRequestGenerator;
use Thomisticus\Generator\Generators\API\APIRoutesGenerator;
use Thomisticus\Generator\Generators\API\APITestGenerator;
use Thomisticus\Generator\Generators\Common\MigrationGenerator;
use Thomisticus\Generator\Generators\Common\ModelGenerator;
use Thomisticus\Generator\Generators\Common\RepositoryGenerator;
use Thomisticus\Generator\Generators\RepositoryTestGenerator;

class RollbackGeneratorCommand extends Command
{
    /**
     * The command Data.
     *
     * @var CommandData
     */
    public $commandData;
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'thomisticus:rollback';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback a full CRUD API and Scaffold for given model';

    /**
     * @var Composer
     */
    public $composer;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->composer = app()['composer'];
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        if (
        !in_array($this->argument('type'), [
            CommandData::$COMMAND_TYPE_API,
            CommandData::$COMMAND_TYPE_SERVICE_SCAFFOLD,
            CommandData::$COMMAND_TYPE_API_SCAFFOLD,
        ])
        ) {
            $this->error('invalid rollback type');
        }

        $this->commandData = new CommandData($this, $this->argument('type'));
        $this->commandData->config->mName = $this->commandData->modelName = $this->argument('model');

        $this->commandData->config->init($this->commandData, ['tableName', 'prefix', 'plural']);

        $migrationGenerator = new MigrationGenerator($this->commandData);
        $migrationGenerator->rollback();

        $modelGenerator = new ModelGenerator($this->commandData);
        $modelGenerator->rollback();

        $repositoryGenerator = new RepositoryGenerator($this->commandData);
        $repositoryGenerator->rollback();

        $requestGenerator = new APIRequestGenerator($this->commandData);
        $requestGenerator->rollback();

        $controllerGenerator = new APIControllerGenerator($this->commandData);
        $controllerGenerator->rollback();

        $routesGenerator = new APIRoutesGenerator($this->commandData);
        $routesGenerator->rollback();

        if ($this->commandData->getAddOn('tests')) {
            $repositoryTestGenerator = new RepositoryTestGenerator($this->commandData);
            $repositoryTestGenerator->rollback();

            $apiTestGenerator = new APITestGenerator($this->commandData);
            $apiTestGenerator->rollback();
        }

        $this->info('Generating autoload files');
        $this->composer->dumpOptimized();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            ['tableName', null, InputOption::VALUE_REQUIRED, 'Table Name'],
            ['prefix', null, InputOption::VALUE_REQUIRED, 'Prefix for all files'],
            ['plural', null, InputOption::VALUE_REQUIRED, 'Plural Model name'],
        ];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['model', InputArgument::REQUIRED, 'Singular Model name'],
            ['type', InputArgument::REQUIRED, 'Rollback type: (api / scaffold / api_scaffold)'],
        ];
    }
}
