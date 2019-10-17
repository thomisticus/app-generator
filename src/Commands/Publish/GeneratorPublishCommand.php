<?php

namespace Thomisticus\Generator\Commands\Publish;

use Thomisticus\Generator\Utils\FileUtil;

class GeneratorPublishCommand extends PublishBaseCommand
{
    /**
     * The console command name.
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
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $this->publishTestCases();
//      $this->publishBaseController();
        $this->publishResponseTrait();

        if (config('thomisticus.crud_generator.options.repository_pattern')) {
            $this->publishBaseRepository();
        }
    }

    /**
     * Replaces dynamic variables of template.
     *
     * @param string $templateData
     *
     * @return string
     */
    private function fillTemplate($templateData)
    {
        $apiVersion = config('thomisticus.crud_generator.api_version', 'v1');
        $apiPrefix = config('thomisticus.crud_generator.api_prefix', 'api');

        $templateData = str_replace('$API_VERSION$', $apiVersion, $templateData);
        $templateData = str_replace('$API_PREFIX$', $apiPrefix, $templateData);
        $appNamespace = $this->getLaravel()->getNamespace();
        $appNamespace = substr($appNamespace, 0, strlen($appNamespace) - 1);
        $templateData = str_replace('$NAMESPACE_APP$', $appNamespace, $templateData);

        $nsRepository = config('thomisticus.crud_generator.namespace.repository', 'App\Repositories');
        $templateData = str_replace('$NAMESPACE_REPOSITORY$', $nsRepository, $templateData);

        $nsTrait = config('thomisticus.crud_generator.namespace.trait', 'App\Traits');
        $templateData = str_replace('$NAMESPACE_TRAIT$', $nsTrait, $templateData);

        return $templateData;
    }

    private function publishTestCases()
    {
        $traitPath = __DIR__ . '/../../../templates/test/api_test_trait.stub';
        $testsPath = config('thomisticus.crud_generator.path.tests', base_path('tests/'));
        $testsNameSpace = config('thomisticus.crud_generator.namespace.tests', 'Tests');
        $createdAtField = config('thomisticus.crud_generator.timestamps.created_at', 'created_at');
        $updatedAtField = config('thomisticus.crud_generator.timestamps.updated_at', 'updated_at');

        $templateData = get_template('test.api_test_trait', 'crud-generator');

        $templateData = str_replace('$NAMESPACE_TESTS$', $testsNameSpace, $templateData);
        $templateData = str_replace('$TIMESTAMPS$', "['$createdAtField', '$updatedAtField']", $templateData);

        $fileName = 'ApiTestTrait.php';

        if (file_exists($testsPath . $fileName) && !$this->confirmOverwrite($fileName)) {
            return;
        }

        FileUtil::createFile($testsPath, $fileName, $templateData);
        $this->info('ApiTestTrait created');

        $testAPIsPath = config('thomisticus.crud_generator.path.api_test', base_path('tests/APIs/'));
        if (!file_exists($testAPIsPath)) {
            FileUtil::createDirectoryIfNotExist($testAPIsPath);
            $this->info('APIs Tests directory created');
        }

        $testRepositoriesPath = config(
            'thomisticus.crud_generator.path.repository_test',
            base_path('tests/Repositories/')
        );
        if (!file_exists($testRepositoriesPath)) {
            FileUtil::createDirectoryIfNotExist($testRepositoriesPath);
            $this->info('Repositories Tests directory created');
        }
    }

    private function publishBaseController()
    {
        $templateData = get_template('app_base_controller', 'crud-generator');

        $templateData = $this->fillTemplate($templateData);

        $controllerPath = app_path('Http/Controllers/');

        $fileName = 'AppBaseController.php';

        if (file_exists($controllerPath . $fileName) && !$this->confirmOverwrite($fileName)) {
            return;
        }

        FileUtil::createFile($controllerPath, $fileName, $templateData);

        $this->info('AppBaseController created');
    }

    private function publishBaseRepository()
    {
        $templateData = get_template('base_repository', 'crud-generator');

        $templateData = $this->fillTemplate($templateData);

        $repositoryPath = app_path('Repositories/');

        FileUtil::createDirectoryIfNotExist($repositoryPath);

        $fileName = 'BaseRepository.php';

        if (file_exists($repositoryPath . $fileName) && !$this->confirmOverwrite($fileName)) {
            return;
        }

        FileUtil::createFile($repositoryPath, $fileName, $templateData);

        $this->info('BaseRepository created');
    }

    private function publishResponseTrait()
    {
        $templateData = get_template('traits.response', 'crud-generator');
        $templateData = $this->fillTemplate($templateData);

        $this->createFile(app_path('Traits/'), 'ResponseTrait.php', $templateData);
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
