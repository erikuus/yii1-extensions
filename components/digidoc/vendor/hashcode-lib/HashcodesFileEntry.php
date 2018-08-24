<?php
/**
 * Class for representing file-entry tag in hashcodes-*.xml file.
 *
 * @internal
 */
class HashcodesFileEntry
{
    private $fullPath;
    private $hash;
    private $size;

    public function __construct($fullPath, $hash, $size)
    {
        $this->fullPath = $fullPath;
        $this->hash = $hash;
        $this->size = $size;
    }

    public function getFullPath()
    {
        return $this->fullPath;
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function getSize()
    {
        return $this->size;
    }
}
