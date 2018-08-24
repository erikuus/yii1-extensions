<?php
/**
 * Class FileHelper
 * Utility methods to work with Digidoc Service files
 */

class FileHelper
{
    /**
     * Makes upload directory
     * @throws Exception
     */
    public static function makeUploadDir()
    {
        $uploadDirectory=SessionHelper::getUploadDirectory();

        if (!file_exists($uploadDirectory) && !mkdir($uploadDirectory)) {
            throw new FileException("There was a problem creating an upload directory '$uploadDirectory'.");
        }

        return $uploadDirectory;
    }

    /**
     * Deletes a directory or a file if one exists on a given path.
     * @param string $path - Path to delete. WARNING! Deletes everything in this path recursively with its contents.
     */
    public static function deleteIfExists($path)
    {
        if (!file_exists($path)) {
            return;
        }
        if (!is_dir($path)) {
            unlink($path);
            return;
        }

        foreach (glob($path.'/*') as $file) {
            if (is_dir($file)) {
                static::deleteIfExists($file);
            } else {
                unlink($file);
            }
        }
        rmdir($path);
    }

    /**
     * Parses file extension. Splits file name by '.' and returns the second half lowered.
     * @param string $filename - File name or path.
     * @return string - File extension as string.
     */
    public static function parseFileExtension($filename)
    {
        $temp = explode('.', $filename);

        return strtolower(end($temp));
    }
}
