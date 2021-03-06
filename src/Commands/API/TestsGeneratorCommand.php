<?php

namespace Thomisticus\Generator\Commands\API;

use Thomisticus\Generator\Commands\BaseCommand;
use Thomisticus\Generator\Generators\API\TestGenerator;
use Thomisticus\Generator\Generators\RepositoryTestGenerator;

class TestsGeneratorCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'thomisticus:tests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create tests command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        parent::handle();

        if ($this->commandData->getOption('repositoryPattern')) {
            (new RepositoryTestGenerator($this->commandData))->generate();
        }

        (new TestGenerator($this->commandData))->generate();

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
