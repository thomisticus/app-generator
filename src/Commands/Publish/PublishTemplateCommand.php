<?php

namespace Thomisticus\Generator\Commands\Publish;

class PublishTemplateCommand extends PublishBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'thomisticus.publish:templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes api generator templates.';

    private $templatesDir;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->templatesDir = config(
            'thomisticus.path.templates_dir',
            base_path('resources/thomisticus/thomisticus-crud-templates/')
        );

        $this->publishGeneratorTemplates();
    }

    /**
     * Publishes templates.
     */
    public function publishGeneratorTemplates()
    {
        $templatesPath = __DIR__ . '/../../../templates';

        return $this->publishDirectory($templatesPath, $this->templatesDir, 'thomisticus-crud-templates');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }
}
