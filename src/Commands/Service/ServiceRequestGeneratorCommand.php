<?php

namespace Thomisticus\Generator\Commands\Service;

use Thomisticus\Generator\Commands\BaseCommand;
use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Generators\Service\RequestGenerator;

class ServiceRequestGeneratorCommand extends BaseCommand
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'thomisticus.service_scaffold:requests';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a full CRUD views for given model';

	/**
	 * Create a new command instance.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->commandData = new CommandData($this, CommandData::$COMMAND_TYPE_SERVICE_SCAFFOLD);
	}

	/**
	 * Execute the command.
	 *
	 * @return void
	 */
	public function handle()
	{
		parent::handle();

		$requestGenerator = new RequestGenerator($this->commandData);
		$requestGenerator->generate();

		$this->performPostActions();
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	public function getOptions()
	{
		return array_merge(parent::getOptions(), []);
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array_merge(parent::getArguments(), []);
	}
}
