<?php

namespace Thomisticus\Generator\Generators\Service;

use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\FileUtil;

class ServiceControllerGenerator extends BaseGenerator
{
	/** @var CommandData */
	private $commandData;

	/** @var string */
	private $path;

	/** @var string */
	private $templateType;

	/** @var string */
	private $fileName;

	public function __construct(CommandData $commandData)
	{
		$this->commandData  = $commandData;
		$this->path         = $commandData->config->pathController;
		$this->templateType = config('thomisticus.crud_generator.templates', 'adminlte-templates');
		$this->fileName     = $this->commandData->modelName . 'Controller.php';
	}

	public function generate()
	{
		if ($this->commandData->getAddOn('datatables')) {
			$templateData = get_template('scaffold.controller.datatable_controller', 'crud-generator');

			$this->generateDataTable();
		} else {
			$templateData = get_template('scaffold.controller.service_controller', 'crud-generator');

			$paginate = $this->commandData->getOption('paginate');

			if ($paginate) {
				$templateData = str_replace('$RENDER_TYPE$', 'paginate(' . $paginate . ')', $templateData);
			} else {
				$templateData = str_replace('$RENDER_TYPE$', 'all()', $templateData);
			}
		}

		$templateData = fill_template($this->commandData->dynamicVars, $templateData);

		FileUtil::createFile($this->path, $this->fileName, $templateData);

		$this->commandData->commandComment("\nController created: ");
		$this->commandData->commandInfo($this->fileName);
	}

	private function generateDataTable()
	{
		$templateData = get_template('scaffold.datatable', 'crud-generator');

		$templateData = fill_template($this->commandData->dynamicVars, $templateData);

		$headerFieldTemplate = get_template('scaffold.views.datatable_column', $this->templateType);

		$headerFields = [];

		foreach ($this->commandData->fields as $field) {
			if (!$field->inIndex) {
				continue;
			}
			$headerFields[] = $fieldTemplate = fill_template_with_field_data(
				$this->commandData->dynamicVars,
				$this->commandData->fieldNamesMapping,
				$headerFieldTemplate,
				$field
			);
		}

		$path = $this->commandData->config->pathDataTables;

		$fileName = $this->commandData->modelName . 'DataTable.php';

		$fields = implode(',' . generate_new_line_tab(1, 3), $headerFields);

		$templateData = str_replace('$DATATABLE_COLUMNS$', $fields, $templateData);

		FileUtil::createFile($path, $fileName, $templateData);

		$this->commandData->commandComment("\nDataTable created: ");
		$this->commandData->commandInfo($fileName);
	}

	public function rollback()
	{
		if ($this->rollbackFile($this->path, $this->fileName)) {
			$this->commandData->commandComment('Controller file deleted: ' . $this->fileName);
		}

		if ($this->commandData->getAddOn('datatables')) {
			if ($this->rollbackFile($this->commandData->config->pathDataTables,
				$this->commandData->modelName . 'DataTable.php')) {
				$this->commandData->commandComment('DataTable file deleted: ' . $this->fileName);
			}
		}
	}
}
