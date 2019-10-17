<?php

namespace Thomisticus\Generator\Commands\Publish;

class PublishTemplateCommand extends PublishBaseCommand
{
    /**
     * The console command name.
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
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $this->templatesDir = config(
            'thomisticus.crud_generator.path.templates_dir',
            base_path('resources/thomisticus/thomisticus-crud-templates/')
        );

        if ($this->publishGeneratorTemplates()) {
            $this->publishScaffoldTemplates();
            $this->publishSwaggerTemplates();
        }
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
     * Publishes templates.
     */
    public function publishScaffoldTemplates()
    {
        $templateType = config('thomisticus.crud_generator.templates', 'adminlte-templates');

        $templatesPath = base_path('vendor/thomisticus/' . $templateType . '/templates/scaffold');

        return $this->publishDirectory(
            $templatesPath,
            $this->templatesDir . '/scaffold',
            'thomisticus-crud-templates/scaffold',
            true
        );
    }

    /**
     * Publishes swagger stemplates.
     */
    public function publishSwaggerTemplates()
    {
        $templatesPath = base_path('vendor/thomisticus/swagger-generator/templates');

        return $this->publishDirectory($templatesPath, $this->templatesDir, 'swagger-generator', true);
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
