<?php

namespace Thomisticus\Generator\Generators\Common;

use File;
use Illuminate\Support\Str;
use SplFileInfo;
use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\FileUtil;

class MigrationGenerator extends BaseGenerator
{
    /**
     * @var CommandData
     */
    private $commandData;

    /**
     * Migration file path
     * @var string
     */
    private $path;

    /**
     * MigrationGenerator constructor.
     * @param $commandData
     */
    public function __construct($commandData)
    {
        $this->commandData = $commandData;
        $this->path = config('app-generator.path.migration', base_path('database/migrations/'));
    }

    /**
     * Generates the migration file
     */
    public function generate()
    {
        $templateData = get_template('migration', 'app-generator');
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);
        $templateData = str_replace('$FIELDS$', $this->generateFields(), $templateData);

        $tableName = $this->commandData->dynamicVars['$TABLE_NAME$'];

        $fileName = date('Y_m_d_His') . '_' . 'create_' . $tableName . '_table.php';

        FileUtil::createFile($this->path, $fileName, $templateData);

        $this->commandData->commandObj->comment("\nMigration created: ");
        $this->commandData->commandObj->info($fileName);
    }

    /**
     * Generates the field lines for the migration file
     * @return string
     */
    private function generateFields()
    {
        $fields = [];
        $foreignKeys = [];
        $createdAtField = null;
        $updatedAtField = null;

        foreach ($this->commandData->fields as $field) {
            if ($field->name == 'created_at') {
                $createdAtField = $field;
                continue;
            } else {
                if ($field->name == 'updated_at') {
                    $updatedAtField = $field;
                    continue;
                }
            }

            $fields[] = $field->migrationText;
            if (!empty($field->foreignKeyText)) {
                $foreignKeys[] = $field->foreignKeyText;
            }
        }

        if ($createdAtField && $updatedAtField) {
            $fields[] = '$table->timestamps();';
        } else {
            if ($createdAtField) {
                $fields[] = $createdAtField->migrationText;
            }
            if ($updatedAtField) {
                $fields[] = $updatedAtField->migrationText;
            }
        }

        if ($this->commandData->getOption('softDelete')) {
            $fields[] = '$table->softDeletes();';
        }

        return implode(generate_new_line_tab(1, 3), array_merge($fields, $foreignKeys));
    }

    /**
     * Rollback migration file creation
     */
    public function rollback()
    {
        $fileName = 'create_' . $this->commandData->config->tableName . '_table.php';

        /** @var SplFileInfo $allFiles */
        $allFiles = File::allFiles($this->path);

        $files = [];
        foreach ($allFiles as $file) {
            $files[] = $file->getFilename();
        }

        $files = array_reverse($files);

        foreach ($files as $file) {
            if (Str::contains($file, $fileName)) {
                if ($this->rollbackFile($this->path, $file)) {
                    $this->commandData->commandObj->comment('Migration file deleted: ' . $file);
                }
                break;
            }
        }
    }
}
