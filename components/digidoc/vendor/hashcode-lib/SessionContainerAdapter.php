<?php
/**
 * Adapter for using FileContainers as StringContainers.
 *
 * Provides {@link StringContainer} interface to {@linl FileContainer}
 * using temporary files stored in {@link DigidocSession}
 */

class SessionContainerAdapter implements StringContainerInterface
{
    private $session;
    private $fileContainer;

    /**
     * Default constructor.
     *
     * Use {@link DigidocSession::containerFromString} to create instance from string.
     *
     * @param DigidocSession         $session
     * @param FileContainerInterface $fileContainer
     */
    public function __construct(DigidocSession $session, FileContainerInterface $fileContainer)
    {
        $this->session = $session;
        $this->fileContainer = $fileContainer;
    }

    public function getDataFiles()
    {
        return $this->fileContainer->getDataFiles();
    }

    public function isHashcodesFormat()
    {
        return $this->fileContainer->isHashcodesFormat();
    }

    public function toDatafilesFormat($datafiles)
    {
        $newFile = $this->session->createFile();

        return new SessionContainerAdapter(
            $this->session,
            $this->fileContainer->writeWithDataFiles($newFile, $datafiles)
        );
    }

    public function toHashcodeFormat()
    {
        $newFile = $this->session->createFile();

        return new SessionContainerAdapter(
            $this->session,
            $this->fileContainer->writeAsHashcodes($newFile)
        );
    }

    public function toString()
    {
        return $this->fileContainer->toString();
    }

    public function getContainerFormat()
    {
        return $this->fileContainer->getContainerFormat();
    }
}
