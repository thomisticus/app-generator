<?php

namespace Thomisticus\Generator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Generators\API\ControllerGenerator;
use Thomisticus\Generator\Generators\API\RequestGenerator;
use Thomisticus\Generator\Generators\API\RouteGenerator;
use Thomisticus\Generator\Generators\API\TestGenerator;
use Thomisticus\Generator\Generators\Common\FactoryGenerator;
use Thomisticus\Generator\Generators\Common\MigrationGenerator;
use Thomisticus\Generator\Generators\Common\ModelGenerator;
use Thomisticus\Generator\Generators\Common\RepositoryGenerator;
use Thomisticus\Generator\Generators\RepositoryTestGenerator;
use Thomisticus\Generator\Generators\Common\SeederGenerator;
use Thomisticus\Generator\Generators\API\ServiceGenerator;
use Thomisticus\Generator\Utils\FileUtil;

class BaseCommand extends Command
{
    /**
     * The command Data.
     *
     * @var CommandData
     */
    public $commandData;

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

        $this->commandData = new CommandData($this, CommandData::$COMMAND_TYPE_API);
        $this->composer = app()['composer'];
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->commandData->modelName = $this->argument('model');
        $this->commandData->initCommandData()->setFieldsAndRelations();
    }

    /**
     * Generate common items
     * Eg: Migration, Model, Repository, Factory and Seeder
     */
    public function generateCommonItems()
    {
        if (!$this->commandData->getOption('fromTable') && !$this->isSkip('migration')) {
            (new MigrationGenerator($this->commandData))->generate();
        }

        if (!$this->isSkip('model')) {
            (new ModelGenerator($this->commandData))->generate();
        }

        if (!$this->isSkip('repository') && $this->commandData->getOption('repositoryPattern')) {
            (new RepositoryGenerator($this->commandData))->generate();
        }

        if ($this->commandData->getOption('factory') || (!$this->isSkip('tests') && $this->commandData->getAddOn('tests'))) {
            (new FactoryGenerator($this->commandData))->generate();
        }

        if ($this->commandData->getOption('seeder')) {
            (new SeederGenerator($this->commandData))->generate()->updateMainSeeder();
        }
    }

    /**
     * Generates API Items
     * Eg: Request, Controller, Service, Routes and Repository
     */
    public function generateAPIItems()
    {
        if (!$this->isSkip('requests') && !$this->isSkip('api_requests')) {
            (new RequestGenerator($this->commandData))->generate();
        }

        if (!$this->isSkip('controller') && !$this->isSkip('api_controller')) {
            (new ControllerGenerator($this->commandData))->generate();
        }

//        if (!$this->isSkip('services') && !$this->isSkip('scaffold_service')) {
//            (new ServiceGenerator($this->commandData))->generate();
//        }

        if (!$this->isSkip('routes') && !$this->isSkip('api_routes')) {
            (new RouteGenerator($this->commandData))->generate();
        }

        if (!$this->isSkip('tests') && $this->commandData->getAddOn('tests')) {
            if ($this->commandData->getOption('repositoryPattern')) {
                (new RepositoryTestGenerator($this->commandData))->generate();
            }

            (new TestGenerator($this->commandData))->generate();
        }
    }

    /**
     * Actions to be performed after file generations, such as saving json schema file of generated files,
     * run the migration and also run the "composer dump-autoload"
     *
     * @param bool $runMigration Whether is to run the migrations or not
     */
    public function performPostActions($runMigration = false)
    {
        if ($this->commandData->getOption('save')) {
            $this->saveSchemaFile();
        }

        if ($runMigration && $this->canRunMigration()) {
            $this->runMigration();
        }

        if (!$this->isSkip('dump-autoload')) {
            $this->info('Generating autoload files');
            $this->composer->dumpOptimized();
        }
    }

    /**
     * Execute database migration
     *
     * @return bool
     */
    public function runMigration()
    {
        $migrationPath = config('app-generator.path.migration', database_path('migrations/'));
        $path = Str::after($migrationPath, base_path()); // get path after base_path
        $this->call('migrate', ['--path' => $path, '--force' => true]);

        return true;
    }

    /**
     * Checks if the database migration can be run
     *
     * @return bool
     */
    public function canRunMigration()
    {
        if ($this->commandData->getOption('forceMigrate')) {
            return true;
        }

        if (!$this->commandData->getOption('fromTable') && !$this->isSkip('migration')) {
            $requestFromConsole = (php_sapi_name() == 'cli');

            if ($requestFromConsole &&
                ($this->commandData->getOption('jsonFromGUI') ||
                    $this->confirm("\nDo you want to migrate database? [y|N]", false))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if is to skip specific option/file during generation.
     *
     * @param string $skip Eg: (migration, model, controller, repository, request, routes, tests, dump-autoload)
     * @return bool
     */
    public function isSkip($skip)
    {
        if ($this->commandData->getOption('skip')) {
            return in_array($skip, (array)$this->commandData->getOption('skip'));
        }

        return false;
    }

    /**
     * Save model schema file at model_schemas folder. Useful for backup and versioning for generated cruds
     */
    private function saveSchemaFile()
    {
        $fileFields = [];

        foreach ($this->commandData->fields as $field) {
            $fileFields[] = [
                'name' => $field->name,
                'dbType' => $field->dbInput,
                'htmlType' => $field->htmlInput,
                'validations' => $field->validations,
                'searchable' => $field->isSearchable,
                'fillable' => $field->isFillable,
                'primary' => $field->isPrimary,
                'inForm' => $field->inForm,
                'inIndex' => $field->inIndex,
                'inView' => $field->inView,
            ];
        }

        foreach ($this->commandData->relations as $relation) {
            $fileFields[] = [
                'type' => 'relation',
                'relation' => $relation->type . ',' . implode(',', $relation->inputs),
            ];
        }

        $path = config('app-generator.path.schema_files', resource_path('model_schemas/'));

        $fileName = $this->commandData->modelName . '.json';

        if (file_exists($path . $fileName) && !$this->confirmOverwrite($fileName)) {
            return;
        }

        FileUtil::createFile($path, $fileName, json_encode($fileFields, JSON_PRETTY_PRINT));
        $this->commandData->commandObj->comment("\nSchema File saved: ");
        $this->commandData->commandObj->info($fileName);
    }

    /**
     * Confirm file overwrite
     *
     * @param string $fileName
     * @param string $prompt
     * @return mixed
     */
    protected function confirmOverwrite($fileName, $prompt = '')
    {
        $prompt = (empty($prompt))
            ? $fileName . ' already exists. Do you want to overwrite it? [y|N]'
            : $prompt;

        return $this->confirm($prompt, false);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            ['fieldsFile', null, InputOption::VALUE_REQUIRED, 'Fields input as json file'],
            ['jsonFromGUI', null, InputOption::VALUE_REQUIRED, 'Direct Json string while using GUI interface'],
            ['plural', null, InputOption::VALUE_REQUIRED, 'Plural Model name'],
            ['tableName', null, InputOption::VALUE_REQUIRED, 'Table Name'],
            ['fromTable', null, InputOption::VALUE_NONE, 'Generate from existing table'],
            ['ignoreFields', null, InputOption::VALUE_REQUIRED, 'Ignore fields while generating from table'],
            ['save', null, InputOption::VALUE_NONE, 'Save model schema to file'],
            ['primary', null, InputOption::VALUE_REQUIRED, 'Custom primary key'],
            ['prefix', null, InputOption::VALUE_REQUIRED, 'Prefix for all files'],
            ['paginate', null, InputOption::VALUE_REQUIRED, 'Pagination for index.blade.php'],
            [
                'skip',
                null,
                InputOption::VALUE_REQUIRED,
                'Skip Specific Items to Generate (migration,model,controller,api_controller,scaffold_controller,repository,requests,api_requests,scaffold_requests,routes,api_routes,scaffold_routes,tests,dump-autoload)'
            ],
            ['relations', null, InputOption::VALUE_NONE, 'Specify if you want to pass relationships for fields'],
            ['softDelete', null, InputOption::VALUE_NONE, 'Soft Delete Option'],
            ['forceMigrate', null, InputOption::VALUE_NONE, 'Specify if you want to run migration or not'],
            ['factory', null, InputOption::VALUE_NONE, 'To generate factory'],
            ['seeder', null, InputOption::VALUE_NONE, 'To generate seeder'],
            ['repositoryPattern', null, InputOption::VALUE_REQUIRED, 'Repository Pattern'],
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
        ];
    }
}
