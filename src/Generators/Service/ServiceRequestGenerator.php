<?php

namespace Thomisticus\Generator\Generators\Service;

use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\FileUtil;

class ServiceRequestGenerator extends BaseGenerator
{
	/** @var CommandData */
	private $commandData;

	/** @var string */
	private $path;

	/** @var string */
	private $requestFileName;

	public function __construct(CommandData $commandData)
	{
		$this->commandData     = $commandData;
		$this->path            = $commandData->config->pathRequest;
		$this->requestFileName = $this->commandData->modelName . 'Request.php';
	}

	public function generate()
	{
		$this->generateRequest();
	}

	private function generateRequest()
	{
		$templateData = get_template('scaffold.request.single_request', 'crud-generator');

		$templateData = fill_template($this->commandData->dynamicVars, $templateData);

		FileUtil::createFile($this->path, $this->requestFileName, $templateData);

		$this->commandData->commandComment("\nSingle Request created: ");
		$this->commandData->commandInfo($this->requestFileName);
	}

	public function rollback()
	{
		if ($this->rollbackFile($this->path, $this->requestFileName)) {
			$this->commandData->commandComment('Create Single Request file deleted: ' . $this->requestFileName);
		}
	}
}
