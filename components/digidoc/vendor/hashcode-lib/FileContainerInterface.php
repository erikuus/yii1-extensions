<?php
/**
 * Container with files as input and output.
 *
 * Converting between container formats is possible through write* methods.
 * Write methods return new FileContainer-s pointing to new files.
 *
 * @author Madis Loitmaa
 *
 */

interface FileContainerInterface extends ContainerInterface
{

    /**
     * Writes container in hashcodes format to new file.
     *
     * @param string $hashcodesFilename file name to write to.
     *
     * @return \SK\Digidoc\FileContainerInterface New container, pointing to new file.
     */
    public function writeAsHashcodes($hashcodesFilename);

    /**
     * Writes container in datafiles format.
     *
     * @param string              $filename  file name to write to.
     * @param DataFileInterface[] $datafiles array of {@link DataFile}-s, to replace hashcodes with.
     *                                       return FileContainer New container new cointainer, pointing to new file.
     */
    public function writeWithDataFiles($filename, $datafiles);

    public function getContainerFormat();
}
