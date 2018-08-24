<?php
/**
 * Abstraction for data file in Container.
 *
 * You can provide your own implementation for example for reading files from database blob.
 *
 * @author Madis Loitmaa
 *
 */
interface DataFileInterface
{

    /**
     * Get file name (local name without folder).
     *
     * @return string file name
     */
    public function getName();

    /**
     * Get file size in bytes.
     *
     * @return int file size in bytes
     */
    public function getSize();

    /**
     * Get file contents.
     *
     * @return string file contents
     */
    public function getContent();

    /**
     * Is data file encoded as multi line Base64 string or one line
     *
     * @return bool
     */
    public function isMMultiLine();

    /**
     * Get DataFile element unmodified or NULL if there is no
     * DataFile tag present in document container
     *
     * @return {string|null}
     */
    public function getRawContent();
}
