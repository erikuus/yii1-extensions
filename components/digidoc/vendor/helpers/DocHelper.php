<?php
/**
 * Class DocHelper
 * Utility class to help working with BDOC and DDOC containers
 */

class DocHelper
{
    /**
     * Helper method for fetching the uploaded container from PHP $_FILES array. If the uploaded container is BDOC
     * then the contents of the container are Base64 encoded.
     * @param string $containerUploadInputName Name of the input used to upload the container.
     * @return string Contents of the hashcode container file.
     * @throws DigidocException
     */
    public static function getEncodedHashcodeVersionOfContainer($containerUploadInputName)
    {
        $hashcodeSession = static::getHashcodeSession();

        $doc = $hashcodeSession->containerFromString(
            file_get_contents($_FILES[$containerUploadInputName]['tmp_name'])
        );
        $hashcodeContents = $doc->toHashcodeFormat()->toString();

        if ($doc->getContainerFormat() === 'BDOC 2.1') {
            $hashcodeContents = base64_encode($hashcodeContents);
        }

        return $hashcodeContents;
    }

    /**
     * Returns the hashcode container session and if there is none then initiates one.
     * @return DigidocSession
     */
    public static function getHashcodeSession()
    {
    	if (!isset($_REQUEST['hashcodeSession'])) {
            static::setHashcodeSession();
        }

        return $_REQUEST['hashcodeSession'];
    }

    /**
     * Method for converting container in hashcode form to a container with files.
     * @param string $containerData - Contents of the container. If container type is BDOC
     * then container_data should be Base64 encoded.
     * @param DataFileInterface[] $datafiles - Array of FileSystemDataFile instances
     * @return string - Path to the created container
     * @throws Exception
     */
    public static function createContainerWithFiles($containerData, $datafiles)
    {
    	$pathToCreatedDoc = SessionHelper::getUploadDirectory().DIRECTORY_SEPARATOR.SessionHelper::getOriginalContainerName();
        $hashcodeSession = static::getHashcodeSession();
        $hashcodeContainer = $hashcodeSession->containerFromString($containerData);
        $withFilesContainerContents = $hashcodeContainer->toDatafilesFormat($datafiles)->toString();

        FileHelper::deleteIfExists($pathToCreatedDoc);
        $handler = fopen($pathToCreatedDoc, 'w');
        fwrite($handler, $withFilesContainerContents);
        fclose($handler);

        return $pathToCreatedDoc;
    }

    /**
     * Updates the container in DDS session with a new datafile.
     * @param DigiDocService $dds - DigiDocService instance
     * @param string $pathToDatafile - Path to where the datafile is located
     * @param string[] $datafileMimeType - Mime Type of the datafile that is to be added
     * @throws DigidocException
     * @throws Exception
     */
    public static function addDatafileViaDds($dds, $pathToDatafile, $datafileMimeType)
    {
        $containerType = DocHelper::getContainerType(SessionHelper::getOriginalContainerName());
        $digestType = $containerType === 'BDOC' ? BdocContainer::DEFAULT_HASH_ALGORITHM : 'sha1';
        $data = file_get_contents($pathToDatafile);

        $datafileId = static::getNextDatafileId($dds);

        $digestValue = $containerType === 'BDOC'
            ? BdocContainer::datafileHashcode($data)
            : DdocContainer::datafileHashcode($filename, $datafileId, $datafileMimeType, $data);

        $dds->AddDataFile(
            array(
                'Sesscode'    => SessionHelper::getDdsSessionCode(),
                'FileName'    => basename($pathToDatafile),
                'MimeType'    => $datafileMimeType,
                'ContentType' => 'HASHCODE',
                'Size'        => filesize($pathToDatafile),
                'DigestType'  => $digestType,
                'DigestValue' => $digestValue,
            )
        );
    }

    /**
     * Method determines the container type with the help of file extension.
     * One can determine the container type with more foolproof and clever ways if necessary.
     * For example by looking if the content of the file are XML or something else. As an example this will do.
     * @param string $filename - Filename of the container which type is to be determined.
     * @return string - Type of container(BDOC or DDOC).
     * @throws DigidocException In case the file extension is unknown.
     */
    public static function getContainerType($filename)
    {
        $extension = FileHelper::parseFileExtension($filename);
        if ($extension === 'bdoc' || $extension === 'asice' || $extension === 'sce') {
            return 'BDOC';
        } elseif ($extension === 'ddoc') {
            return 'DDOC';
        }
        throw new DigidocException("Unknown container with file extension '$extension'.");
    }

    /**
     * In case of DDOC, it is important that DataFiles are indexed correctly so this mehtod helps to figure out
     * what index should the next potential DataFile in container carry.
     * @param DigiDocService $dds - DigiDocService instance
     * @return string - ID of the next potential datafile if one would be added to the container in session.
     * @throws Exception
     */
    public static function getNextDatafileId($dds)
    {
        if (!file_exists(
            SessionHelper::getUploadDirectory().DIRECTORY_SEPARATOR.SessionHelper::getOriginalContainerName()
        )
        ) {
            return 'D0';
        }
        $datafiles = static::getDatafilesFromContainer();
        $noOfDatafiles = count($datafiles);
        if ($noOfDatafiles === 0) {
            return 'D0';
        }

        return 'D'.static::getFirstMissingDatafileNoFromDds($dds);
    }

    /**
     * Extracts the datafiles from a container.
     * @return array - Array of all the FileSystemDataFile instances that the container in session holds.
     * @throws DigidocException
     * @throws Exception
     */
    public static function getDatafilesFromContainer()
    {
        $originalContainerPath = SessionHelper::getUploadDirectory().
            DIRECTORY_SEPARATOR.
            SessionHelper::getOriginalContainerName();

        $doc = static::getContainerType(SessionHelper::getOriginalContainerName()) === 'BDOC' ?
            new BdocContainer($originalContainerPath) : new DdocContainer($originalContainerPath);

        return $doc->getDataFiles();
    }

    /**
     * Helper method for determining next potential datafiles index. It looks if there is a datafile missing
     * from the middle of array and if there is it returns it's index. For example if container has datafiles
     * with indexes D0, D1 and D3 then it would return 2. If the indexes array is complete and there is nothing missing
     * then it returns 0.
     * @param DigiDocService $dds - DigiDocService instance
     */
    private static function getFirstMissingDatafileNoFromDds($dds)
    {
        $signedDocInfo = $dds->GetSignedDocInfo(array('Sesscode' => SessionHelper::getDdsSessionCode()));
        $documentFileInfo = $signedDocInfo['SignedDocInfo'];
        if (null !== $documentFileInfo && isset($documentFileInfo->DataFileInfo)) {
            if (isset($documentFileInfo->DataFileInfo->Id)) {
                $dataFiles = array($documentFileInfo->DataFileInfo);
            } else {
                $dataFiles = $documentFileInfo->DataFileInfo;
            }

            for ($i = 0; ; $i++) {
                $foundId = false;
                foreach ($dataFiles as &$data_file) {
                    if ('D'.$i === $data_file->Id) {
                        $foundId = true;
                        break;
                    }
                }
                if (!$foundId) {
                    return $i;
                }
            }

        }

        return 0;
    }

    /**
     * Method does'nt actually remove anything from anywhere. It just returns the datafiles in the session container
     * except the one that is named.
     * @param string $toBeRemovedName - Name of the datafile that is to be removed.
     * @return array - Datafiles minus the removed one.
     */
    public static function removeDatafile($toBeRemovedName)
    {
        $datafiles = DocHelper::getDatafilesFromContainer();
        $dataFileId = 0;
        foreach ($datafiles as &$datafile) {
            if ($datafile->getName() == $toBeRemovedName) {
                unset($datafiles[$dataFileId]);
            }
            $dataFileId++;
        }

        return $datafiles;
    }

    /**
     * Method gives info about the desired outcome container format gotten from the dropdown menu in the example
     * applications index.
     * @param string $containerTypeInputName - Name of the selectbox where container type is selected.
     * @return array - Specification of the container that was selected as an array.
     * @throws DigidocException - If container type was unknown or unspecified, an Exception is thrown.
     */
    public static function getDesiredContainerType($containerTypeInputName)
    {
        if (!array_key_exists($containerTypeInputName, $_POST)) {
            throw new DigidocException('Invalid container type.');
        }

        $validContainerTypes = array('BDOC 2.1', 'DIGIDOC-XML 1.3');
        $chosenContainerType = $_POST[$containerTypeInputName];
        if (!in_array($chosenContainerType, $validContainerTypes, true)) {
            throw new DigidocException("Invalid container type '$chosenContainerType'.");
        }

        list($parts, $shortType) = static::containerShortType($chosenContainerType);

        return array(
            'format'    => $parts[0],
            'version'   => $parts[1],
            'shortType' => $shortType,
        );
    }

    /**
     * This should be called in the end of every request where session with DDS is already started.
     * It saves the hashcode container session to HTTP session.
     */
    public static function persistHashcodeSession()
    {
        $hashcodeSession = static::getHashcodeSession();
        $_SESSION['hashcodeSession'] = $hashcodeSession;
    }

    /**
     * In the creation of new container there it is named by the first datafile it contains. This helper method
     * helps to figure out this new containers file name.
     * @param string $uploadedFileName - Name of the datafile which gives its name to the container.
     * @param string $containerType    - File extension of the container(bdoc or ddoc)
     * @return string - Derived name of the container.
     */
    public static function getNewCMontainerName($uploadedFileName, $containerType)
    {
        $positionOfFirstDot = strpos($uploadedFileName, '.');
        $containerType = strtolower($containerType);
        if ($positionOfFirstDot === false) {
            return $uploadedFileName.'.'.$containerType;
        }

        return substr($uploadedFileName, 0, $positionOfFirstDot).'.'.$containerType;
    }

    /**
     * Start hashcode session if not started
     */
    public static function setHashcodeSession()
    {
        if (isset($_SESSION['hashcodeSession'])) {
            $_REQUEST['hashcodeSession'] = $_SESSION['hashcodeSession'];
        } else {
            $ddoc = new Digidoc();
            $_REQUEST['hashcodeSession'] = $ddoc->createSession();
        }
    }

    /**
     * @param $chosenContainerType
     * @return array
     */
    private static function containerShortType($chosenContainerType)
    {
        $parts = explode(' ', $chosenContainerType);
        $shortType = 'BDOC';
        if ($parts[0] === 'DIGIDOC-XML') {
            $shortType = 'DDOC';

            return array($parts, $shortType);
        }

        return array($parts, $shortType);
    }
}