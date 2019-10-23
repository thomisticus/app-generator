<?php

namespace Thomisticus\Generator;

use Illuminate\Support\ServiceProvider;
use Thomisticus\Generator\Commands\API\APIControllerGeneratorCommand;
use Thomisticus\Generator\Commands\API\APIGeneratorCommand;
use Thomisticus\Generator\Commands\API\APIRequestsGeneratorCommand;
use Thomisticus\Generator\Commands\API\TestsGeneratorCommand;
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
            'thomisticus:api' => APIGeneratorCommand::class,
            'thomisticus.api:controller' => APIControllerGeneratorCommand::class,
            'thomisticus.api:requests' => APIRequestsGeneratorCommand::class,
            'thomisticus:service' => ServiceGeneratorCommand::class,
            'thomisticus.api:tests' => TestsGeneratorCommand::class,
            'thomisticus:migration' => MigrationGeneratorCommand::class,
            'thomisticus:model' => ModelGeneratorCommand::class,
            'thomisticus:repository' => RepositoryGeneratorCommand::class,
            'thomisticus:rollback' => RollbackGeneratorCommand::class,
        ];

        $this->commands($commands);
    }
}
