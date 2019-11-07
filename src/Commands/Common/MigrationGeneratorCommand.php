<?php

namespace Thomisticus\Generator\Commands\Common;

use Thomisticus\Generator\Commands\BaseCommand;
use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Generators\Common\MigrationGenerator;

class MigrationGeneratorCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'thomisticus:migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create migration command';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        parent::handle();

        if ($this->commandData->getOption('fromTable')) {
            $this->error('fromTable option is not allowed to use with migration generator');
            return;
        }

        (new MigrationGenerator($this->commandData))->generate();

        $this->performPostActions(true);
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
