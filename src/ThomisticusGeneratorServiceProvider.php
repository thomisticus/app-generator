<?php

namespace Thomisticus\Generator;

use Illuminate\Support\ServiceProvider;
use Thomisticus\Generator\Commands\API\APIControllerGeneratorCommand;
use Thomisticus\Generator\Commands\API\APIGeneratorCommand;
use Thomisticus\Generator\Commands\API\APIRequestsGeneratorCommand;
use Thomisticus\Generator\Commands\API\TestsGeneratorCommand;
use Thomisticus\Generator\Commands\APIScaffoldGeneratorCommand;
use Thomisticus\Generator\Commands\Common\MigrationGeneratorCommand;
use Thomisticus\Generator\Commands\Common\ModelGeneratorCommand;
use Thomisticus\Generator\Commands\Common\RepositoryGeneratorCommand;
use Thomisticus\Generator\Commands\Publish\GeneratorPublishCommand;
use Thomisticus\Generator\Commands\RollbackGeneratorCommand;
use Thomisticus\Generator\Commands\Scaffold\ControllerGeneratorCommand;
use Thomisticus\Generator\Commands\Scaffold\RequestsGeneratorCommand;
use Thomisticus\Generator\Commands\Scaffold\ScaffoldGeneratorCommand;
use Thomisticus\Generator\Commands\Service\ServiceControllerGeneratorCommand;
use Thomisticus\Generator\Commands\Service\ServiceGeneratorCommand;
use Thomisticus\Generator\Commands\Service\ServiceRequestGeneratorCommand;
use Thomisticus\Generator\Commands\Service\ServiceScaffoldGeneratorCommand;

class ThomisticusGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../config/crud_generator.php';

        $this->publishes([
            $configPath => config_path('thomisticus/crud_generator.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('thomisticus.publish', function ($app) {
            return new GeneratorPublishCommand();
        });

        $this->app->singleton('thomisticus.api', function ($app) {
            return new APIGeneratorCommand();
        });

        $this->app->singleton('thomisticus.scaffold', function ($app) {
            return new ScaffoldGeneratorCommand();
        });

        $this->app->singleton('thomisticus.service_scaffold', function ($app) {
            return new ServiceScaffoldGeneratorCommand();
        });

        $this->app->singleton('thomisticus.api_scaffold', function ($app) {
            return new APIScaffoldGeneratorCommand();
        });

        $this->app->singleton('thomisticus.migration', function ($app) {
            return new MigrationGeneratorCommand();
        });

        $this->app->singleton('thomisticus.model', function ($app) {
            return new ModelGeneratorCommand();
        });

        $this->app->singleton('thomisticus.repository', function ($app) {
            return new RepositoryGeneratorCommand();
        });

        $this->app->singleton('thomisticus.service', function ($app) {
            return new ServiceGeneratorCommand();
        });

        $this->app->singleton('thomisticus.api.controller', function ($app) {
            return new APIControllerGeneratorCommand();
        });

        $this->app->singleton('thomisticus.api.requests', function ($app) {
            return new APIRequestsGeneratorCommand();
        });

        $this->app->singleton('thomisticus.api.tests', function ($app) {
            return new TestsGeneratorCommand();
        });

        $this->app->singleton('thomisticus.scaffold.controller', function ($app) {
            return new ControllerGeneratorCommand();
        });

        $this->app->singleton('thomisticus.service_scaffold.controller', function ($app) {
            return new ServiceControllerGeneratorCommand();
        });

        $this->app->singleton('thomisticus.scaffold.requests', function ($app) {
            return new RequestsGeneratorCommand();
        });

        $this->app->singleton('thomisticus.service_scaffold.requests', function ($app) {
            return new ServiceRequestGeneratorCommand();
        });

        $this->app->singleton('thomisticus.rollback', function ($app) {
            return new RollbackGeneratorCommand();
        });

        $this->commands([
            'thomisticus.publish',
            'thomisticus.api',
            'thomisticus.scaffold',
            'thomisticus.service_scaffold',
            'thomisticus.api_scaffold',
            'thomisticus.publish.templates',
            'thomisticus.migration',
            'thomisticus.model',
            'thomisticus.repository',
            'thomisticus.service',
            'thomisticus.api.controller',
            'thomisticus.api.requests',
            'thomisticus.api.tests',
            'thomisticus.scaffold.controller',
            'thomisticus.scaffold.requests',
            'thomisticus.rollback',
        ]);
    }
}
