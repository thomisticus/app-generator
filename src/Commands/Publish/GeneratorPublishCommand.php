<?php

namespace Thomisticus\Generator\Commands\Publish;

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
//		$this->publishBaseController();
		$this->publishExceptionHandlerTrait();
		$this->publishResponseTrait();
		$this->publishBaseRepository();
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
		$apiPrefix  = config('thomisticus.crud_generator.api_prefix', 'api');

		$templateData = str_replace('$API_VERSION$', $apiVersion, $templateData);
		$templateData = str_replace('$API_PREFIX$', $apiPrefix, $templateData);
		$appNamespace = $this->getLaravel()->getNamespace();
		$appNamespace = substr($appNamespace, 0, strlen($appNamespace) - 1);
		$templateData = str_replace('$NAMESPACE_APP$', $appNamespace, $templateData);

		$nsRepository = config('thomisticus.crud_generator.namespace.repository', 'App\Repositories');
		$templateData = str_replace('$NAMESPACE_REPOSITORY$', $nsRepository, $templateData);

		$nsTrait      = config('thomisticus.crud_generator.namespace.trait', 'App\Traits');
		$templateData = str_replace('$NAMESPACE_TRAIT$', $nsTrait, $templateData);

		return $templateData;
	}

	private function publishTestCases()
	{
		$traitPath = __DIR__ . '/../../../templates/test/api_test_trait.stub';
		$testsPath = config('thomisticus.crud_generator.path.api_test', base_path('tests/'));

		$this->publishFile($traitPath, $testsPath . 'ApiTestTrait.php', 'ApiTestTrait.php');

		if (!file_exists($testsPath . 'traits/')) {
			mkdir($testsPath . 'traits/');
			$this->info('traits directory created');
		}
	}

	private function publishBaseController()
	{
		$templateData = get_template('app_base_controller', 'crud-generator');
		$templateData = $this->fillTemplate($templateData);

		$this->createFile(app_path('Http/Controllers/'), 'AppBaseController.php', $templateData);
	}

	private function publishBaseRepository()
	{
		$templateData = get_template('base_repository', 'crud-generator');
		$templateData = $this->fillTemplate($templateData);

		$this->createFile(app_path('Repositories/'), 'BaseRepository.php', $templateData);
	}

	private function publishExceptionHandlerTrait()
	{
		$templateData = get_template('traits.exception_handler', 'crud-generator');
		$templateData = $this->fillTemplate($templateData);

		$this->createFile(app_path('Traits/'), 'ExceptionHandlerTrait.php', $templateData);
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
