<?php
/**
 * Utility class for manipulating hashcodes-*.xml files inside {@link BdocContainer}s.
 *
 * @author Madis Loitmaa
 * @internal
 */

class HashcodesXml
{
    const HASH_CODES_ELEMENT_NAME = 'hashcodes';
    const FILE_ENTRY_ELEMENT_NAME = 'file-entry';
    const FILE_ENTRY_ELEMENT_ATTRIBUTE_FULL_PATH = 'full-path';
    const FILE_ENTRY_ELEMENT_ATTRIBUTE_HASH = 'hash';
    const FILE_ENTRY_ELEMENT_ATTRIBUTE_SIZE = 'size';

    /**
     * Parse hashcode.xml file XML content
     *
     * @param string $xml
     *
     * @return array
     */
    public static function parse($xml)
    {
        $hashcodes = new SimpleXMLElement($xml);
        $fileEntries = array();

        foreach ($hashcodes->children() as $child) {
            $fileEntries[] = static::xmlElementToFileEntry($child);
        }

        return $fileEntries;
    }

    public static function dataFilesToHashcodesXml($datafiles, $hashAlgorithm)
    {
        $fileEntries = array();
        foreach ($datafiles as $datafile) {
            $fileEntries[] = static::convertDataFileToFileEntry($datafile, $hashAlgorithm);
        }

        return static::write($fileEntries);

    }

    public static function convertDataFileToFileEntry(DataFileInterface $datafile, $hashAlgorithm)
    {
        return new HashcodesFileEntry(
            $datafile->getName(),
            base64_encode(hash($hashAlgorithm, $datafile->getContent(), true)),
            $datafile->getSize()
        );
    }

    /**
     * @param HashcodesFileEntry $fileEntries
     *
     * @return string
     */
    public static function write($fileEntries)
    {
        $rootElement = new SimpleXMLElement('<'.static::HASH_CODES_ELEMENT_NAME.'/>');
        foreach ($fileEntries as $fe) {
            static::fileEntryToXmlElem($fe, $rootElement->addChild(static::FILE_ENTRY_ELEMENT_NAME));
        }

        return static::getXml($rootElement);
    }

    private static function xmlElementToFileEntry(SimpleXMLElement $fileEntry)
    {
        $fullPathAttribute = static::FILE_ENTRY_ELEMENT_ATTRIBUTE_FULL_PATH;

        return new HashcodesFileEntry(
            (string) $fileEntry->attributes()->$fullPathAttribute,
            (string) $fileEntry->attributes()->hash,
            (int) $fileEntry->attributes()->size
        );
    }

    private static function fileEntryToXmlElem(HashcodesFileEntry $hashcodesFileEntry, \SimpleXMLElement $elem)
    {
        $elem->addAttribute(static::FILE_ENTRY_ELEMENT_ATTRIBUTE_FULL_PATH, $hashcodesFileEntry->getFullPath());
        $elem->addAttribute(static::FILE_ENTRY_ELEMENT_ATTRIBUTE_HASH, $hashcodesFileEntry->getHash());
        $elem->addAttribute(static::FILE_ENTRY_ELEMENT_ATTRIBUTE_SIZE, $hashcodesFileEntry->getSize());
    }

    /**
     * @param \SimpleXMLElement $element
     *
     * @return string Returns full XML on successful conversion or empty string
     */
    private static function getXml(\SimpleXMLElement $element)
    {
        $getTest = $element->asXML();
        if ($getTest === false) {
            $getTest = '';
        }

        return $getTest;
    }
}
