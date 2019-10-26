<?php

namespace Thomisticus\Generator\Commands\Publish;

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
     *
     * @return mixed
     */
    public function handle()
    {
        $this->publishTestCases();
//      $this->publishBaseController();
        $this->publishResponseTrait();

        if (config('app-generator.options.repository_pattern')) {
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
        $replacers = [
            '$API_VERSION$' => config('app-generator.api_version', 'v1'),
            '$API_PREFIX$' => config('app-generator.api_prefix', 'api'),
            '$NAMESPACE_APP$' => rtrim($this->getLaravel()->getNamespace(), '\\'),
            '$NAMESPACE_REPOSITORY$' => config('app-generator.namespace.repository', 'App\Repositories'),
            '$NAMESPACE_TRAIT$' => config('app-generator.namespace.trait', 'App\Traits'),
            '$NAMESPACE_TESTS$' => config('app-generator.namespace.tests', 'Tests'),
            '$TEST_TIMESTAMPS$' => "['" . config('app-generator.timestamps.created_at', 'created_at') . "', '" .
                config('app-generator.timestamps.updated_at', 'updated_at') . "']"
        ];

        return str_replace(array_keys($replacers), $replacers, $templateData);
    }

    private function fillAndCreateFile($templateName, $filePath, $fileName)
    {
        $templateData = get_template($templateName, 'app-generator');
        $templateData = $this->fillTemplate($templateData);

        $this->createFile($filePath, $fileName, $templateData);
    }

    private function publishTestCases()
    {
        $testsPath = config('app-generator.path.tests', base_path('tests/'));
        $this->fillAndCreateFile('test.api_test_trait', $testsPath, 'ApiTestTrait.php');

        $testAPIsPath = config('app-generator.path.api_test', base_path('tests/APIs/'));
        if (!file_exists($testAPIsPath)) {
            FileUtil::createDirectoryIfNotExist($testAPIsPath);
            $this->info('APIs Tests directory created');
        }

        $testRepositoriesPath = config('thomisticus.path.repository_test', base_path('tests/Repositories/'));
        if (!file_exists($testRepositoriesPath)) {
            FileUtil::createDirectoryIfNotExist($testRepositoriesPath);
            $this->info('Repositories Tests directory created');
        }
    }

    private function publishBaseController()
    {
        $controllerPath = config('app-generator.path.api_controller', app_path('Http/Controllers/'));
        $this->fillAndCreateFile('app_base_controller', $controllerPath, 'AppBaseController.php');
    }

    private function publishBaseRepository()
    {
        $repositoriesPath = config('app-generator.path.repository', app_path('Repositories/'));
        $this->fillAndCreateFile('base_repository', $repositoriesPath, 'BaseRepository.php');
    }

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
