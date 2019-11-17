<?php

namespace Thomisticus\Generator;

use Illuminate\Support\ServiceProvider;
use Thomisticus\Generator\Commands\API\ControllerGeneratorCommand;
use Thomisticus\Generator\Commands\API\ApiGeneratorCommand;
use Thomisticus\Generator\Commands\API\RequestGeneratorCommand;
use Thomisticus\Generator\Commands\API\TestsGeneratorCommand;
use Thomisticus\Generator\Commands\Common\FactoryGeneratorCommand;
use Thomisticus\Generator\Commands\Common\MigrationGeneratorCommand;
use Thomisticus\Generator\Commands\Common\ModelGeneratorCommand;
use Thomisticus\Generator\Commands\Common\RepositoryGeneratorCommand;
use Thomisticus\Generator\Commands\Publish\GeneratorPublishCommand;
use Thomisticus\Generator\Commands\Publish\PublishTemplateCommand;
use Thomisticus\Generator\Commands\RollbackGeneratorCommand;
use Thomisticus\Generator\Commands\API\ServiceGeneratorCommand;

class ThomisticusGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../config/app-generator.php';

        $this->publishes([
            $configPath => config_path('app-generator.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $commands = [
            'thomisticus:publish' => GeneratorPublishCommand::class,
            'thomisticus.publish:templates' => PublishTemplateCommand::class,
            'thomisticus:api' => ApiGeneratorCommand::class,
            'thomisticus:controller' => ControllerGeneratorCommand::class,
            'thomisticus:request' => RequestGeneratorCommand::class,
            'thomisticus:service' => ServiceGeneratorCommand::class,
            'thomisticus:tests' => TestsGeneratorCommand::class,
            'thomisticus:migration' => MigrationGeneratorCommand::class,
            'thomisticus:factory' => FactoryGeneratorCommand::class,
            'thomisticus:model' => ModelGeneratorCommand::class,
            'thomisticus:repository' => RepositoryGeneratorCommand::class,
            'thomisticus:rollback' => RollbackGeneratorCommand::class,
        ];

        $this->commands($commands);
    }
}
