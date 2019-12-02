<?php

namespace Thomisticus\Generator\Generators\Common;

use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Utils\FileUtil;

/**
 * Class SeederGenerator.
 */
class SeederGenerator extends BaseGenerator
{
    /**
     * @var CommandData
     */
    private $commandData;

    /**
     * Database seeder file path
     * @var string
     */
    private $path;

    /**
     * Database seeder file name
     * @var string
     */
    private $fileName;

    /**
     * ModelGenerator constructor.
     *
     * @param CommandData $commandData
     */
    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->paths['seeder'];
        $this->fileName = $this->commandData->config->modelNames['plural'] . 'TableSeeder.php';
    }

    /**
     * Generates the database seeder
     * @return $this
     */
    public function generate()
    {
        $templateData = get_template('seeds.model_seeder', 'app-generator');
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandObj->line("- Seeder created: <info>{$this->fileName}</info>");

        return $this;
    }

    /**
     *
     * @return $this|void
     */
    public function updateMainSeeder()
    {
        $mainSeederContent = file_get_contents($this->commandData->config->paths['database_seeder']);
        $pluralModelName = $this->commandData->config->modelNames['plural'];
        $newSeederStatement = '$this->call(' . $pluralModelName . 'TableSeeder::class);';

        if (strpos($mainSeederContent, $newSeederStatement)) {
            $infoText = '> ' . $this->commandData->config->modelNames['plural'];
            $infoText .= 'TableSeeder entry found in DatabaseSeeder. Skipping adjustment.';

            $this->commandData->commandObj->warn($infoText);
            return;
        }

        $newSeederStatement = generate_tabs(2) . $newSeederStatement . generate_new_line();

        preg_match_all('/\\$this->call\\((.*);/', $mainSeederContent, $matches);

        $totalMatches = count($matches[0]);
        $lastSeederStatement = $matches[0][$totalMatches - 1];

        $replacePosition = strpos($mainSeederContent, $lastSeederStatement);

        $mainSeederContent = substr_replace(
            $mainSeederContent,
            $newSeederStatement,
            $replacePosition + strlen($lastSeederStatement) + 1,
            0
        );

        file_put_contents($this->commandData->config->paths['database_seeder'], $mainSeederContent);
        $this->commandData->commandObj->comment('Main Seeder file updated.');

        return $this;
    }

    /**
     * Rollback the seeder generation
     */
    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandObj->line("- Seeder file deleted: <info>{$this->fileName}</info>");
        }
    }
}
