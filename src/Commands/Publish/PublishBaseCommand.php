<?php

namespace Thomisticus\Generator\Commands\Publish;

use File;
use Thomisticus\Generator\Commands\BaseCommand;
use Thomisticus\Generator\Utils\FileUtil;

class PublishBaseCommand extends BaseCommand
{
    /**
     * Ignoring BaseCommand's handle method
     */
    public function handle()
    {
    }

    /**
     * Copies an already created file to the destination path
     *
     * @param string $sourceFile
     * @param string $destinationFile
     * @param string $fileName
     */
    public function publishFile($sourceFile, $destinationFile, $fileName)
    {
        if (file_exists($destinationFile) && !$this->confirmOverwrite($destinationFile)) {
            return;
        }

        copy($sourceFile, $destinationFile);

        $this->comment($fileName . ' published');
        $this->info($destinationFile);
    }

    /**
     * Creates a specific file
     *
     * @param string $filePath
     * @param string $fileName
     * @param string $templateData
     */
    public function createFile($filePath, $fileName, $templateData)
    {
        if (file_exists($filePath . $fileName) && !$this->confirmOverwrite($fileName)) {
            return;
        }

        FileUtil::createFile($filePath, $fileName, $templateData);

        $this->info($fileName . ' created');
    }

    /**
     * Copies the whole directory with its files to a new path
     *
     * @param string $sourceDir
     * @param string $destinationDir
     * @param string $dirName
     * @param bool $force
     *
     * @return bool|void
     */
    public function publishDirectory($sourceDir, $destinationDir, $dirName, $force = false)
    {
        if (file_exists($destinationDir) && !$force && !$this->confirmOverwrite($destinationDir)) {
            return;
        }

        File::makeDirectory($destinationDir, 493, true, true);
        File::copyDirectory($sourceDir, $destinationDir);

        $this->comment($dirName . ' published');
        $this->info($destinationDir);

        return true;
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
