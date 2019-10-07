<?php

namespace Thomisticus\Generator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Generators\API\APIControllerGenerator;
use Thomisticus\Generator\Generators\API\APIRequestGenerator;
use Thomisticus\Generator\Generators\API\APIRoutesGenerator;
use Thomisticus\Generator\Generators\API\APITestGenerator;
use Thomisticus\Generator\Generators\FactoryGenerator;
use Thomisticus\Generator\Generators\MigrationGenerator;
use Thomisticus\Generator\Generators\ModelGenerator;
use Thomisticus\Generator\Generators\RepositoryGenerator;
use Thomisticus\Generator\Generators\RepositoryTestGenerator;
use Thomisticus\Generator\Generators\Scaffold\ControllerGenerator;
use Thomisticus\Generator\Generators\Scaffold\MenuGenerator;
use Thomisticus\Generator\Generators\Scaffold\RequestGenerator;
use Thomisticus\Generator\Generators\Scaffold\RoutesGenerator;
use Thomisticus\Generator\Generators\Scaffold\ViewGenerator;
use Thomisticus\Generator\Generators\SeederGenerator;
use Thomisticus\Generator\Generators\Service\ServiceControllerGenerator;
use Thomisticus\Generator\Generators\Service\ServiceGenerator;
use Thomisticus\Generator\Generators\Service\ServiceRequestGenerator;
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

        $this->composer = app()['composer'];
    }

    public function handle()
    {
        $this->commandData->modelName = $this->argument('model');

        $this->commandData->initCommandData();
        $this->commandData->getFields();
    }

    public function generateCommonItems()
    {
        if (!$this->commandData->getOption('fromTable') && !$this->isSkip('migration')) {
            $migrationGenerator = new MigrationGenerator($this->commandData);
            $migrationGenerator->generate();
        }

        if (!$this->isSkip('model')) {
            $modelGenerator = new ModelGenerator($this->commandData);
            $modelGenerator->generate();
        }

        if (!$this->isSkip('repository') && $this->commandData->getOption('repositoryPattern')) {
            $repositoryGenerator = new RepositoryGenerator($this->commandData);
            $repositoryGenerator->generate();
        }

        if ($this->commandData->getOption('factory') || (
                !$this->isSkip('tests') && $this->commandData->getAddOn('tests')
            )) {
            $factoryGenerator = new FactoryGenerator($this->commandData);
            $factoryGenerator->generate();
        }

        if ($this->commandData->getOption('seeder')) {
            $seederGenerator = new SeederGenerator($this->commandData);
            $seederGenerator->generate();
            $seederGenerator->updateMainSeeder();
        }
    }

    public function generateAPIItems()
    {
        if (!$this->isSkip('requests') && !$this->isSkip('api_requests')) {
            $requestGenerator = new APIRequestGenerator($this->commandData);
            $requestGenerator->generate();
        }

        if (!$this->isSkip('controllers') && !$this->isSkip('api_controller')) {
            $controllerGenerator = new APIControllerGenerator($this->commandData);
            $controllerGenerator->generate();
        }

        if (!$this->isSkip('routes') && !$this->isSkip('api_routes')) {
            $routesGenerator = new APIRoutesGenerator($this->commandData);
            $routesGenerator->generate();
        }

        if (!$this->isSkip('tests') && $this->commandData->getAddOn('tests')) {
            if ($this->commandData->getOption('repositoryPattern')) {
                $repositoryTestGenerator = new RepositoryTestGenerator($this->commandData);
                $repositoryTestGenerator->generate();
            }

            $apiTestGenerator = new APITestGenerator($this->commandData);
            $apiTestGenerator->generate();
        }
    }

    public function generateScaffoldItems()
    {
        if (!$this->isSkip('requests') && !$this->isSkip('scaffold_requests')) {
            $requestGenerator = new RequestGenerator($this->commandData);
            $requestGenerator->generate();
        }

        if (!$this->isSkip('controllers') && !$this->isSkip('scaffold_controller')) {
            $controllerGenerator = new ControllerGenerator($this->commandData);
            $controllerGenerator->generate();
        }

        if (!$this->isSkip('views')) {
            $viewGenerator = new ViewGenerator($this->commandData);
            $viewGenerator->generate();
        }

        if (!$this->isSkip('routes') && !$this->isSkip('scaffold_routes')) {
            $routeGenerator = new RoutesGenerator($this->commandData);
            $routeGenerator->generate();
        }

        if (!$this->isSkip('menu') && $this->commandData->config->getAddOn('menu.enabled')) {
            $menuGenerator = new MenuGenerator($this->commandData);
            $menuGenerator->generate();
        }
    }

    public function generateServiceItems()
    {
        if (!$this->isSkip('requests') && !$this->isSkip('scaffold_requests')) {
            $requestGenerator = new ServiceRequestGenerator($this->commandData);
            $requestGenerator->generate();
        }

        if (!$this->isSkip('controllers') && !$this->isSkip('scaffold_controller')) {
            $controllerGenerator = new ServiceControllerGenerator($this->commandData);
            $controllerGenerator->generate();
        }

        if (!$this->isSkip('services') && !$this->isSkip('scaffold_service')) {
            $serviceGenerator = new ServiceGenerator($this->commandData);
            $serviceGenerator->generate();
        }

//        if (!$this->isSkip('views')) {
//            $viewGenerator = new ViewGenerator($this->commandData);
//            $viewGenerator->generate();
//        }

//        if (!$this->isSkip('routes') && !$this->isSkip('scaffold_routes')) {
//            $routeGenerator = new RoutesGenerator($this->commandData);
//            $routeGenerator->generate();
//        }

//        if (!$this->isSkip('menu') && $this->commandData->config->getAddOn('menu.enabled')) {
//            $menuGenerator = new MenuGenerator($this->commandData);
//            $menuGenerator->generate();
//        }

        if (!$this->isSkip('tests') && $this->commandData->getAddOn('tests')) {
            if (!$this->isSkip('repository') && $this->commandData->getOption('repositoryPattern')) {
                $repositoryTestGenerator = new RepositoryTestGenerator($this->commandData);
                $repositoryTestGenerator->generate();
            }

            $apiTestGenerator = new APITestGenerator($this->commandData);
            $apiTestGenerator->generate();
        }
    }

    public function performPostActions($runMigration = false)
    {
        if ($this->commandData->getOption('save')) {
            $this->saveSchemaFile();
        }

        if ($runMigration) {
            if ($this->commandData->getOption('forceMigrate')) {
                $this->runMigration();
            } elseif (!$this->commandData->getOption('fromTable') && !$this->isSkip('migration')) {
                $requestFromConsole = (php_sapi_name() == 'cli') ? true : false;
                if ($this->commandData->getOption('jsonFromGUI') && $requestFromConsole) {
                    $this->runMigration();
                } elseif ($requestFromConsole && $this->confirm("\nDo you want to migrate database? [y|N]", false)) {
                    $this->runMigration();
                }
            }
        }
        if (!$this->isSkip('dump-autoload')) {
            $this->info('Generating autoload files');
            $this->composer->dumpOptimized();
        }
    }

    public function runMigration()
    {
        $migrationPath = config('thomisticus.crud_generator.path.migration', database_path('migrations/'));
        $path = Str::after($migrationPath, base_path()); // get path after base_path
        $this->call('migrate', ['--path' => $path, '--force' => true]);

        return true;
    }

    public function isSkip($skip)
    {
        if ($this->commandData->getOption('skip')) {
            return in_array($skip, (array)$this->commandData->getOption('skip'));
        }

        return false;
    }

    public function performPostActionsWithMigration()
    {
        $this->performPostActions(true);
    }

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

        $path = config('thomisticus.crud_generator.path.schema_files', resource_path('model_schemas/'));

        $fileName = $this->commandData->modelName . '.json';

        if (file_exists($path . $fileName) && !$this->confirmOverwrite($fileName)) {
            return;
        }
        FileUtil::createFile($path, $fileName, json_encode($fileFields, JSON_PRETTY_PRINT));
        $this->commandData->commandComment("\nSchema File saved: ");
        $this->commandData->commandInfo($fileName);
    }

    /**
     * @param        $fileName
     * @param string $prompt
     *
     * @return bool
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
            ['jsonResponse', null, InputOption::VALUE_REQUIRED, 'To methods return jsons'],
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
                'Skip Specific Items to Generate (migration,model,controllers,api_controller,scaffold_controller,repository,requests,api_requests,scaffold_requests,routes,api_routes,scaffold_routes,views,tests,menu,dump-autoload)'
            ],
            ['datatables', null, InputOption::VALUE_REQUIRED, 'Override datatables settings'],
            [
                'views',
                null,
                InputOption::VALUE_REQUIRED,
                'Specify only the views you want generated: index,create,edit,show'
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
