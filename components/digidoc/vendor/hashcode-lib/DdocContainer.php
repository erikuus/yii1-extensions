<?php
/**
 * Abstraction of a bdoc file.
 * Implementation of {@link FileContainer} for DDOC files.
 */

class DdocContainer implements FileContainerInterface
{
    private static $dataFileNodeName = 'DataFile';
    private static $contentTypeHashCode = 'HASHCODE';
    private static $containerFormatVersion = 'DIGIDOC-XML 1.3';
    private static $supporteDdocVersions = array('1.1', '1.2', '1.3');
    private $filename;

    /**
     * Constructs ddoc container from filename.
     *
     * @param string $filename container file path
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->validateFormatAndVersion();
    }

    /**
     * Utility method to calculate file hashcode for using with DDS AddDataFile mehtod.
     *
     * @param string $filename filename
     * @param string $fileId   file id. D0 for first file D1 for second and so on...
     * @param string $mimetype file mime type
     * @param string $content  file content
     *
     * @return string file base64 encode file hashcode
     */
    public static function datafileHashcode($filename, $fileId, $mimetype, $content)
    {
        $attributes = array(
            'xmlns'       => 'http://www.sk.ee/DigiDoc/v1.3.0#',
            'ContentType' => 'EMBEDDED_BASE64',
            'Filename'    => $filename,
            'Id'          => $fileId,
            'MimeType'    => $mimetype,
            'Size'        => strlen($content),
        );

        $xml = '<DataFile';
        foreach ($attributes as $key => $val) {
            $xml .= " $key=\"".htmlspecialchars($val)."\"";
        }

        $xml .= '>'.static::consistantBase64Encoder($content, true);
        $xml .= '</DataFile>';

        $encodedHash = base64_encode(sha1($xml, true));

        return $encodedHash;
    }

    /**
     * Tests if passed in data is supported document format and version.
     * DdocContainer class supports DIGIDOC-XML versions from 1.1 to 1.3.
     *
     * @param string $xmlData ddoc file contents.
     *
     * @return boolean true if format and version are supported.
     */
    public static function isSupportedFormatAndVersion($xmlData)
    {
        $xmlReader = new \XMLReader();
        $xmlReader->XML($xmlData);

        return static::isValidFormatAndVersion($xmlReader);

    }

    /**
     * Get all {DdocDataFile} in DDOC container
     *
     * @return array
     */
    public function getDataFiles()
    {
        $xml = new \XMLReader();
        $xml->open($this->filename);
        $datafiles = array();

        while ($xml->read()) {
            if ($xml->localName === static::$dataFileNodeName && $xml->nodeType === \XMLReader::ELEMENT) {
                $datafiles[] = new DdocDataFile($this->filename, $xml->getAttribute('Filename'));
            }
        }
        $xml->close();

        return $datafiles;
    }

    /**
     * Check {DdocDataFile} in DDOC container are in hashcode format
     *
     * @return bool
     */
    public function isHashcodesFormat()
    {
        $xml = new \XMLReader();
        $xml->open($this->filename);
        $result = false;

        while ($xml->read()) {
            if ($xml->localName === static::$dataFileNodeName && $xml->nodeType === \XMLReader::ELEMENT) {
                $result = $xml->getAttribute('ContentType') === static::$contentTypeHashCode;
                break;
            }
        }
        $xml->close();

        return $result;
    }

    /**
     * Convert DDOC container into hashcode formatted DDOC container
     *
     * @param string $hashcodesFilename
     *
     * @return \SK\Digidoc\DdocContainer
     */
    public function writeAsHashcodes($hashcodesFilename)
    {
        $signedDoc = new \SimpleXMLElement(file_get_contents($this->filename));

        /** @var \SimpleXMLElement $child */
        foreach ($signedDoc->children() as $child) {
            if ($child->getName() === static::$dataFileNodeName) {
                $this->convertToHashcode($child);
            }
        }
        $signedDoc->asXML($hashcodesFilename);

        return new DdocContainer($hashcodesFilename);
    }

    /**
     * Convert hashcode formatted DDOC container back into normal DDOC container
     *
     * @param string                          $ddocFilename
     * @param \SK\Digidoc\DataFileInterface[] $datafiles
     *
     * @return \SK\Digidoc\DdocContainer
     */
    public function writeWithDataFiles($ddocFilename, $datafiles)
    {
        $signedDoc = simplexml_load_file($this->filename);

        $datafilesByName = array_combine(array_map(function (DataFileInterface $datafile) {
            return $datafile->getName();
        }, $datafiles), $datafiles);
        $this->convertHashcodeToFile($signedDoc, $datafilesByName);
        $signedDoc->asXML($ddocFilename);

        return new DdocContainer($ddocFilename);
    }

    /**
     * Get DDOC container format and version
     *
     * @return string
     */
    public function getContainerFormat()
    {
        return static::$containerFormatVersion;
    }

    /**
     * Get DDOC container as {string}
     *
     * @return string
     */
    public function toString()
    {
        return file_get_contents($this->filename);
    }

    private function validateFormatAndVersion()
    {
        $xmlReader = new \XMLReader();
        $xmlReader->open($this->filename);
        if (!static::isValidFormatAndVersion($xmlReader)) {
            throw new DigidocException('Invalid container format or version. Only Digidoc versions from 1.1 to 1.3 are supported.');
        }
    }

    private static function isValidFormatAndVersion(\XMLReader $xmlReader)
    {
        $result = false;
        while ($xmlReader->read()) {
            if ($xmlReader->localName === 'SignedDoc' && $xmlReader->nodeType === \XMLReader::ELEMENT) {
                $ddocVersion = $xmlReader->getAttribute('version');
                $result = $xmlReader->getAttribute('format') === 'DIGIDOC-XML'
                    && in_array($ddocVersion, static::$supporteDdocVersions, false);
                break;
            }
        }
        $xmlReader->close();

        return $result;
    }

    private static function consistantBase64Encoder($content, $multiLine)
    {
        $encodedContent = base64_encode($content);

        return $multiLine ? chunk_split($encodedContent, 64, "\n") : $encodedContent."\n";
    }

    private function convertToHashcode(\SimpleXMLElement $datafileXml)
    {
        $datafile = new DdocDataFile($this->filename, $datafileXml->attributes()->Filename);
        $hashcodeValue = $datafile->hashcode();
        $datafileXml->attributes()->ContentType = static::$contentTypeHashCode;
        $datafileXml->addAttribute('DigestType', 'sha1');
        $datafileXml->addAttribute('DigestValue', $hashcodeValue);

        $datafileXml[0] = null;
    }

    private function convertHashcodeToFile(\SimpleXMLElement $datafileXml, array $datafiles)
    {
        $dataFileToChange = dom_import_simplexml($datafileXml);
        $dataFileTags = $dataFileToChange->parentNode->getElementsByTagName(static::$dataFileNodeName);
        $existingDdoc = false;

        /** @var \DOMElement $dataFileTag */
        foreach ($dataFileTags as $dataFileTag) {
            $fileName = $dataFileTag->getAttribute('Filename');

            /** @var DataFileInterface $file */
            $file = $datafiles[$fileName];
            if ($file->getRawContent() !== null) {
                $existingDdoc = true;
                $this->replaceDataFileTag($file, $dataFileTag, $dataFileToChange);
            } else {
                $this->changeDataFileTag($dataFileTag, $file);
            }
        }

        if ($existingDdoc) {
            $this->fixDataFileNamespace($dataFileToChange);
        }
    }

    /**
     * @param DataFileInterface $file
     * @param \DOMElement       $dataFileTag
     * @param \DOMElement       $dataFileToChange
     *
     * @return \DOMElement
     */
    private function replaceDataFileTag($file, $dataFileTag, $dataFileToChange)
    {
        $fileXml = dom_import_simplexml(simplexml_load_string($file->getRawContent()));
        $replacementImport = $dataFileToChange->ownerDocument->importNode($fileXml, true);
        $dataFileTag->parentNode->replaceChild($replacementImport, $dataFileTag);

        return $dataFileToChange;
    }

    /**
     * @param $dataFileTag
     * @param $file
     */
    private function changeDataFileTag(\DOMElement $dataFileTag, DataFileInterface $file)
    {
        $dataFileTag->removeAttribute('DigestType');
        $dataFileTag->removeAttribute('DigestValue');
        $dataFileTag->setAttribute('ContentType', 'EMBEDDED_BASE64');

        $dataFileTag->nodeValue = static::consistantBase64Encoder($file->getContent(), $file->isMMultiLine());
    }

    /**
     * @param $dataFileToChange
     */
    private function fixDataFileNamespace(\DOMElement $dataFileToChange)
    {
        $namespace = $dataFileToChange->lookupNamespaceUri(null);
        $signedDoc = simplexml_import_dom($dataFileToChange);
        /** @var \SimpleXMLElement $child */
        foreach ($signedDoc->children() as $child) {
            $childNamespace = $child->getNamespaces();
            if ($child->getName() === static::$dataFileNodeName && count($childNamespace) > 0) {
                $child->addAttribute('xmlns', $namespace);
            }
        }
    }
}
