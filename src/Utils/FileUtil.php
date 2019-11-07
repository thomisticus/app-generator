<?php

namespace Thomisticus\Generator\Utils;

class FileUtil
{
    /**
     * Create a file checking if the folders in the path already exists, otherwise it will create the folders as well
     *
     * @param string $path
     * @param string $fileName
     * @param string $contents
     */
    public static function createFile($path, $fileName, $contents)
    {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $path = $path . $fileName;

        file_put_contents($path, $contents);
    }

    /**
     * Creates a directory if it doesn't exist yet with the possibility to replace it
     *
     * @param string $path
     * @param bool $replace
     */
    public static function createDirectoryIfNotExist($path, $replace = false)
    {
        if (file_exists($path) && $replace) {
            rmdir($path);
        }

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Deleting file if it exists, using unlink
     *
     * @param string $path
     * @param $fileName
     * @return bool
     */
    public static function deleteFile($path, $fileName)
    {
        if (file_exists($path . $fileName)) {
            return unlink($path . $fileName);
        }

        return false;
    }
}
