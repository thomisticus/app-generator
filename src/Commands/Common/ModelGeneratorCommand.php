<?php

namespace Thomisticus\Generator\Commands\Common;

use Thomisticus\Generator\Commands\BaseCommand;
use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Generators\Common\ModelGenerator;

class ModelGeneratorCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'thomisticus:model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create model command';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        parent::handle();

        (new ModelGenerator($this->commandData))->generate();

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
