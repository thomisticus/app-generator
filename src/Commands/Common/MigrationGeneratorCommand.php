<?php

namespace Thomisticus\Generator\Commands\Common;

use Thomisticus\Generator\Commands\BaseCommand;
use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Generators\Common\MigrationGenerator;

class MigrationGeneratorCommand extends BaseCommand
{
    /**
     * The console command name.
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
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        if ($this->commandData->getOption('fromTable')) {
            $this->error('fromTable option is not allowed to use with migration generator');

            return;
        }

        $migrationGenerator = new MigrationGenerator($this->commandData);
        $migrationGenerator->generate();

        $this->performPostActionsWithMigration();
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
