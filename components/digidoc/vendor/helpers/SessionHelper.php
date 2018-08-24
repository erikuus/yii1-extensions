<?php
/**
 * Class SessionHelper
 */

class SessionHelper
{
    /**
     * Check if there is open session then try to close it
     * @param DigiDocService $dds
     * @param string $uploadDirectory the directory where the uploaded files are copied and temporary files stored.
     * @param string $originalContainerName
     * @throws Exception
     */
    public static function initDdsSession($dds, $uploadDirectory, $originalContainerName)
    {
		// Start the Session with DDS
		$startSessionResponse = $dds->StartSession(array('bHoldSession' => 'true'));
		$ddsSessionCode = $startSessionResponse['Sesscode'];

		// Set parameters necessary for the next potential requests.
		$_SESSION['ddsSessionCode'] = $ddsSessionCode;
		$_SESSION['uploadDirectory'] = $uploadDirectory.$ddsSessionCode;;
		$_SESSION['originalContainerName'] = $originalContainerName;
    }

    /**
     * Check if there is open session then try to close it and delete session directory
     * @param DigiDocService $dds
     * @throws Exception
     */
    public static function killDdsSession($dds)
    {
        if (array_key_exists('ddsSessionCode', $_SESSION))
        {
            // if the session data of previous dds session still exists we will initiate a cleanup
            FileHelper::deleteIfExists(static::getUploadDirectory());

            // close session
            $dds->CloseSession(array(
            	'Sesscode'=>static::getDdsSessionCode()
            ));
        }

        // end the hashcode container session
        DocHelper::getHashcodeSession()->end();
    }

    /**
     * Helper method for getting the DigiDocService session code from HTTP session.
     * @return string - Session code of the current DigiDocService session.
     * @throws Exception - It is expected that if this method is called then dds session is started and session code is
     * loaded to HTTP session. If it is not so then an exception is thrown.
     */
    public static function getDdsSessionCode()
    {
        if (!isset($_SESSION['ddsSessionCode'])) {
            throw new Exception('There is no active session with DDS.');
        }

        return $_SESSION['ddsSessionCode'];
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function getUploadDirectory()
    {
        if (!isset($_SESSION['uploadDirectory'])) {
            throw new $uploadDirectory('There is no with files version of container, so the container can not be restored.');
        }

        return $_SESSION['uploadDirectory'];
    }

    /**
     * Helper method for getting the name of the container currently handled. Used for example at the moment of
     * downloading the container to restore the original file name.
     * @return string - File name of the container in the moment it was uploaded.
     * @throws DigidocException- It is expected that if this method is called then dds session is started and the original
     * container name is loaded to HTTP session. If it is not so then an exception is thrown.
     */
    public static function getOriginalContainerName()
    {
        if (!isset($_SESSION['originalContainerName'])) {
            throw new DigidocException('There is no with files version of container, so the container can not be restored.');
        }

        return $_SESSION['originalContainerName'];
    }
}