<?php

namespace Thomisticus\Generator\Commands\Common;

use Thomisticus\Generator\Commands\BaseCommand;
use Thomisticus\Generator\Generators\Common\SeederGenerator;

class SeederGeneratorCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'thomisticus:seeder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create seeder command';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        parent::handle();

        (new SeederGenerator($this->commandData))
            ->generate()
            ->updateMainSeeder();

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
