<?php

namespace Thomisticus\Generator\Generators;

use Thomisticus\Generator\Utils\FileUtil;

class BaseGenerator
{
    /**
     * Rollback a file creation
     *
     * @param string $path
     * @param string $fileName
     * @return bool
     */
    public function rollbackFile($path, $fileName)
    {
        if (file_exists($path . $fileName)) {
            return FileUtil::deleteFile($path, $fileName);
        }

        return false;
    }
}
