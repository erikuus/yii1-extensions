<?php
/**
 * DataFile representing a regular file in file system.
 *
 * @author Madis Loitmaa
 *
 */

class FileSystemDataFile implements DataFileInterface
{

    private $path;

    /**
     * Construct {@link FileSystemDataFile} using file path.
     *
     * @param string $path file path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Get data file contents in file system
     *
     * @return string
     */
    public function getContent()
    {
        return file_get_contents($this->path);
    }

    /**
     * Get data file name in file system
     *
     * @return string
     */
    public function getName()
    {
        return basename($this->path);
    }

    /**
     * Get Base64 encoded content of data file
     *
     * @return null
     */
    public function getRawContent()
    {
        return null;
    }

    /**
     * Get data file size
     *
     * @return int
     */
    public function getSize()
    {
        return filesize($this->path);
    }

    /**
     * Is data file encoded as multi line Base64 string or one line
     *
     * @return bool
     */
    public function isMMultiLine()
    {
        return Digidoc::DDOC_DATA_FILE_CHUNK_SPLIT;
    }
}
