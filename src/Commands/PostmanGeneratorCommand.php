<?php

namespace Thomisticus\Generator\Commands;

use Knuckles\Scribe\Commands\GenerateDocumentation;
use Knuckles\Scribe\Matching\RouteMatcherInterface;
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
     *
     * @param RouteMatcherInterface $routeMatcher
     *
     * @return void
     */
    public function handle(RouteMatcherInterface $routeMatcher)
    {
        if ((new PostmanGenerator($this))->generate()) {
            $filePath = config('app-generator.path.postman', resource_path('docs/'));
            $fileName = config('app-generator.postman.file_name', 'collection.json');

            $filePath = $filePath . $fileName;

            $this->info('Postman collection file created successfully in: file://' . $filePath);
        } else {
            $this->error('Error creating Postman collection.');
        }
    }
}
