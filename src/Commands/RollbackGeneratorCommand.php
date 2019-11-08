<?php

namespace Thomisticus\Generator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Generators\API\ControllerGenerator;
use Thomisticus\Generator\Generators\API\RequestGenerator;
use Thomisticus\Generator\Generators\API\RouteGenerator;
use Thomisticus\Generator\Generators\API\TestGenerator;
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
     * The name and signature of the console command.
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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!in_array($this->argument('type'), [CommandData::$COMMAND_TYPE_API])) {
            $this->error('invalid rollback type');
        }

        $this->commandData = new CommandData($this, $this->argument('type'));
        $this->commandData->config->modelName = $this->commandData->modelName = $this->argument('model');

        $this->commandData->config->init($this->commandData, ['tableName', 'prefix', 'plural']);

        (new MigrationGenerator($this->commandData))->rollback();
        (new ModelGenerator($this->commandData))->rollback();
        (new RepositoryGenerator($this->commandData))->rollback();
        (new RequestGenerator($this->commandData))->rollback();
        (new ControllerGenerator($this->commandData))->rollback();
        (new RouteGenerator($this->commandData))->rollback();

        if ($this->commandData->getAddOn('tests')) {
            (new RepositoryTestGenerator($this->commandData))->rollback();
            (new TestGenerator($this->commandData))->rollback();
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
