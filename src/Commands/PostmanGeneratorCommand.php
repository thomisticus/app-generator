<?php

namespace Thomisticus\Generator\Commands;

use Mpociot\ApiDoc\Commands\GenerateDocumentation;
use Thomisticus\Generator\Generators\PostmanGenerator;

class PostmanGeneratorCommand extends GenerateDocumentation
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'thomisticus:postman {--force : Force rewriting of existing routes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate your API documentation from existing Laravel api routes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        (new PostmanGenerator($this))->generate();
    }
}
