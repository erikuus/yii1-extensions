<?php
/**
 * Container for with string input/output.
 *
 * @author Madis Loitmaa
 *
 */

interface StringContainerInterface extends ContainerInterface
{
    /**
     * Converts container to hashocdes format.
     *
     * @return StringContainerInterface New container.
     */
    public function toHashcodeFormat();

    /**
     * Converts container to datafiles format.
     *
     * @param DataFileInterface[] $datafiles list of datafiles.
     *
     * @return StringContainerInterface New container.
     */
    public function toDatafilesFormat($datafiles);
}
