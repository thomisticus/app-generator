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
        if ((new PostmanGenerator($this))->generate()) {
            $filePath = config('app-generator.path.postman', resource_path('docs/'));
            $fileName = config('app-generator.postman.file_name', 'collection.json');

            $filePath = $filePath . $fileName;

            $this->info('Postman collection file created successfully in: file://' . $filePath);

        } else {
            return $this->error('Error creating Postman collection.');
        }

    }
}
