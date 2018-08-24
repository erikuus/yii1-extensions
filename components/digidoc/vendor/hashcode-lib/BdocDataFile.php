<?php
/**
 * DataFile representing file inside a bdoc file.
 */

class BdocDataFile implements DataFileInterface
{
    private $bdocFilename;
    private $filename;

    /**
     * Constructor with bdoc file path and datafile local name.
     *
     * @param string $bdocFilename bdoc file path
     * @param string $filename     datafile local name
     */
    public function __construct($bdocFilename, $filename)
    {
        $this->bdocFilename = $bdocFilename;
        $this->filename = $filename;
    }

    /**
     * Get BDOC data file content
     *
     * @return string
     */
    public function getContent()
    {
        $zip = new \ZipArchive();

        $zip->open($this->bdocFilename);
        $result = $zip->getFromName($this->filename);
        $zip->close();

        return $result;
    }

    /**
     * Get BDOC data file name
     *
     * @return string
     */
    public function getName()
    {
        return $this->filename;
    }

    /**
     * Get data file as Base64 encoded {string}
     *
     * @return null
     */
    public function getRawContent()
    {
        return null;
    }

    /**
     * Get data file size in bytes
     *
     * @return int
     */
    public function getSize()
    {
        $zip = new \ZipArchive();

        $zip->open($this->bdocFilename);
        $stat = $zip->statName($this->filename);
        $result = $stat['size'];
        $zip->close();

        return $result;
    }

    /**
     * Is data file encoded as multi line Base64 string or one line
     *
     * @return bool
     */
    public function isMMultiLine()
    {
        return false;
    }
}
