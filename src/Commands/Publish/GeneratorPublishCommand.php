<?php

namespace Thomisticus\Generator\Commands\Publish;

use Thomisticus\Generator\Utils\FieldsInputUtil;
use Thomisticus\Generator\Utils\FileUtil;

class GeneratorPublishCommand extends PublishBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'thomisticus:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes & init api routes, base controller, base test cases traits.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->publishTestCases();
        $this->publishBaseController();
        $this->publishBaseRequest();
        $this->publishBaseModel();
        $this->publishBaseService();
        $this->publishResponseTrait();

        if (config('app-generator.options.repository_pattern')) {
            $this->publishBaseRepository();
        }
    }

    /**
     * Replaces dynamic variables of template.
     *
     * @param string $templateData
     * @return string
     */
    private function fillTemplate($templateData)
    {
        $replacers = [
            '$API_VERSION$' => config('app-generator.api_version', 'v1'),
            '$API_PREFIX$' => config('app-generator.api_prefix', 'api'),
            '$NAMESPACE_APP$' => rtrim($this->getLaravel()->getNamespace(), '\\'),
            '$NAMESPACE_REPOSITORY$' => config('app-generator.namespace.repository', 'App\Repositories'),
            '$NAMESPACE_SERVICE$' => config('app-generator.namespace.service', 'App\Services'),
            '$NAMESPACE_REQUEST$' => config('app-generator.namespace.request', 'App\Http\Requests'),
            '$NAMESPACE_MODEL$' => config('app-generator.namespace.model', 'App\Models'),
            '$NAMESPACE_TRAIT$' => config('app-generator.namespace.trait', 'App\Traits'),
            '$NAMESPACE_TESTS$' => config('app-generator.namespace.tests', 'Tests'),
            '$TEST_TIMESTAMPS$' => FieldsInputUtil::prepareValuesArrayString(config('app-generator.timestamps')),
            '$CREATED_AT_COLUMN$' => config('app-generator.timestamps.created_at', 'created_at'),
            '$UPDATED_AT_COLUMN$' => config('app-generator.timestamps.updated_at', 'updated_at'),
            '$DELETED_AT_COLUMN$' => config('app-generator.timestamps.deleted_at', 'deleted_at'),
        ];

        return str_replace(array_keys($replacers), $replacers, $templateData);
    }

    /**
     * Fills the template (replacing the keys) and creates the file
     *
     * @param string $templateName
     * @param string $filePath
     * @param string $fileName
     */
    private function fillAndCreateFile($templateName, $filePath, $fileName)
    {
        $templateData = get_template($templateName, 'app-generator');
        $templateData = $this->fillTemplate($templateData);

        $this->createFile($filePath, $fileName, $templateData);
    }

    /**
     * Publishes the integration tests creating its files and folders
     */
    private function publishTestCases()
    {
        $testsPath = config('app-generator.path.tests', base_path('tests/'));
        $this->fillAndCreateFile('tests.api_test_trait', $testsPath, 'ApiTestTrait.php');

        $testAPIsPath = config('app-generator.path.api_tests', base_path('tests/APIs/'));
        if (!file_exists($testAPIsPath)) {
            FileUtil::createDirectoryIfNotExist($testAPIsPath);
            $this->info('APIs Tests directory created');
        }

        $testRepositoriesPath = config('app-generator.path.repository_test', base_path('tests/Repositories/'));
        if (!file_exists($testRepositoriesPath)) {
            FileUtil::createDirectoryIfNotExist($testRepositoriesPath);
            $this->info('Repositories Tests directory created');
        }
    }

    /**
     *  BaseController file publisher
     */
    private function publishBaseController()
    {
        $controllerPath = config('app-generator.path.controller', app_path('Http/Controllers/'));
        $this->fillAndCreateFile('api.controller.api_base_controller', $controllerPath, 'ApiBaseController.php');
    }

    /**
     *  BaseRequest file publisher
     */
    private function publishBaseRequest()
    {
        $requestPath = config('app-generator.path.request', app_path('Http/Requests/'));
        $this->fillAndCreateFile('api.requests.base_request', $requestPath, 'BaseRequest.php');
    }

    /**
     *  BaseRequest file publisher
     */
    private function publishBaseModel()
    {
        $modelPath = config('app-generator.path.model', app_path('Http/Models/'));
        $this->fillAndCreateFile('api.model.base_model', $modelPath, 'BaseModel.php');
    }

    /**
     *  BaseService file publisher
     */
    private function publishBaseService()
    {
        $servicePath = config('app-generator.path.service', app_path('Http/Services/'));
        $this->fillAndCreateFile('api.services.base_service', $servicePath, 'BaseService.php');
    }

    /**
     * BaseRepository file publisher
     */
    private function publishBaseRepository()
    {
        $repositoriesPath = config('app-generator.path.repository', app_path('Repositories/'));
        $this->fillAndCreateFile('api.repositories.base_repository', $repositoriesPath, 'BaseRepository.php');
    }

    /**
     *  ResponseTrait file publisher
     */
    private function publishResponseTrait()
    {
        $traitPath = config('app-generator.path.trait', app_path('Traits/'));
        $this->fillAndCreateFile('traits.response', $traitPath, 'ResponseTrait.php');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }
}
