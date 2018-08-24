<?php
/**
 * Abstraction for file container (bdoc or ddoc file) that can be converted to hashcodes format.
 *
 * Container can be in datafiles format (normal operation) or hashcodes format.
 * You can use {@link Container::isHashcodesFormat()} to determine which format container currently has.
 * To switch between formats use methods provided by subinterfaces {@link FileContainer} and {@link StringContainer}.
 *
 * @author Madis Loitmaa
 *
 */
interface ContainerInterface
{

    /**
     * Return array of datafiles in this container.
     *
     * @return DataFileInterface[] list of datafiles
     */
    public function getDataFiles();

    /**
     * Checks container format.
     *
     * @return boolean true for hashcodes format, false for datafiles format.
     */
    public function isHashcodesFormat();

    /**
     * Returns entire container as a string.
     *
     * @return string
     */
    public function toString();
}
