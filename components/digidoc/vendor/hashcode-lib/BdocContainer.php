<?php
/**
 * Abstraction of a bdoc file.
 * Implementation of {@link FileContainer} for bdoc files.
 */

class BdocContainer implements FileContainerInterface
{

    const DEFAULT_HASH_ALGORITHM = 'sha256';

    /**
     * Regular expression for finding hashcodes-*.xml files in bdoc container.
     *
     * @var string
     */
    const HASHCODES_FILES_REGEX = '|^META-INF/hashcodes-\\w+.xml$|';

    private $filename;

    /**
     * BdocContainer constructor.
     *
     * @param string $filename BDOC container filename
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Utility method for calculating file hash for DDS AddDataFile method.
     *
     * @param string $content file content
     *
     * @return string file hash
     */
    public static function datafileHashcode($content)
    {
        return base64_encode(hash('sha256', $content, true));
    }

    /**
     * Get BDOC container format name and version
     *
     * @return string
     */
    public function getContainerFormat()
    {
        return 'BDOC 2.1';
    }

    /**
     * @param string $hashcodesFilename
     *
     * @return \SK\Digidoc\BdocContainer
     */
    public function writeAsHashcodes($hashcodesFilename)
    {
        copy($this->filename, $hashcodesFilename);
        $zip = new \ZipArchive();
        $zip->open($hashcodesFilename);
        $this->deleteDataFiles($zip);
        $this->writeHashcodes($zip, $this->getDataFiles());
        $this->writeComment($zip, self::containerComment());
        $zip->close();

        return new BdocContainer($hashcodesFilename);
    }

    /**
     * Get all files included in BDOC container
     *
     * @return array
     */
    public function getDataFiles()
    {
        $zip = new \ZipArchive();
        $zip->open($this->filename);
        $datafiles = array();
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if ($this->isDataFile($filename)) {
                $datafiles[] = new BdocDataFile($this->filename, $filename);
            }
        }
        $zip->close();

        return $datafiles;
    }

    /**
     * @param string                          $bdocFilename
     * @param \SK\Digidoc\DataFileInterface[] $datafiles
     *
     * @return \SK\Digidoc\BdocContainer
     */
    public function writeWithDataFiles($bdocFilename, $datafiles)
    {
        copy($this->filename, $bdocFilename);
        $zip = new \ZipArchive();
        $zip->open($bdocFilename);
        $this->deleteHashcodeFiles($zip);
        foreach ($datafiles as $datafile) {
            $zip->addFromString($datafile->getName(), $datafile->getContent());
        }
        $this->writeComment($zip, self::containerComment());
        $zip->close();

        return new BdocContainer($bdocFilename);
    }

    /**
     * Check if BDOC container is in hashcode format
     *
     * @return bool
     */
    public function isHashcodesFormat()
    {
        $zip = new \ZipArchive();
        $zip->open($this->filename);
        $result = $zip->locateName($this->hashcodesFilename('sha256')) !== false;
        $zip->close();

        return $result;
    }

    /**
     * Convert BDOC container into {string}
     *
     * @return string
     */
    public function toString()
    {
        return file_get_contents($this->filename);
    }

    private function deleteDataFiles(\ZipArchive $zip)
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if ($this->isDataFile($filename)) {
                $zip->deleteName($filename);
            }
        }
    }

    private function isDataFile($filename)
    {
        return $filename !== 'mimetype' && strpos($filename, 'META-INF/') !== 0;
    }

    private function writeHashcodes(\ZipArchive $zip, $datafiles)
    {
        foreach (array('sha256', 'sha512') as $algorithm) {
            $zip->addFromString(
                $this->hashcodesFilename($algorithm),
                HashcodesXml::dataFilesToHashcodesXml($datafiles, $algorithm)
            );
        }
    }

    private function hashcodesFilename($algorithm)
    {
        return "META-INF/hashcodes-$algorithm.xml";
    }

    private function writeComment(\ZipArchive $zip, $comment)
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $zip->setCommentIndex($i, $comment);
        }
    }

    private static function containerComment()
    {
        return sprintf(
            'dds-hashcode %s - PHP %s, %s %s %s',
            Digidoc::version(),
            phpversion(),
            php_uname('s'),
            php_uname('r'),
            php_uname('v')
        );
    }

    private function deleteHashcodeFiles(\ZipArchive $zip)
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if ($this->isHashcodesFile($filename)) {
                $zip->deleteName($filename);
            }
        }
    }

    private function isHashcodesFile($filename)
    {
        return preg_match(self::HASHCODES_FILES_REGEX, $filename);
    }
}
