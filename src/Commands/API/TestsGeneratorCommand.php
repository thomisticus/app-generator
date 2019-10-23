<?php

namespace Thomisticus\Generator\Commands\API;

use Thomisticus\Generator\Commands\BaseCommand;
use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Generators\API\APITestGenerator;
use Thomisticus\Generator\Generators\RepositoryTestGenerator;

class TestsGeneratorCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'thomisticus.api:tests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create tests command';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        parent::handle();

        (new RepositoryTestGenerator($this->commandData))->generate();
        (new APITestGenerator($this->commandData))->generate();

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
