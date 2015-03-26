<?php
/**
 * XSFtp class file.
 *
 * XSFtp handles SFTP functionalities
 *
 * The following code is the component registration in the config file:
 *
 * <pre>
 * 'components'=>array(
 *     'ftp'=>array(
 *         'class'=>'ext.components.sftp.XSFtp',
 *         'host'=>'127.0.0.1',
 *         'port'=>22,
 *         'username'=>'yourusername',
 *         'password'=>'yourpassword',
 *     )
 * )
 * </pre>
 *
 * @link http://www.yiiframework.com/extension/sftp/
 * @author	Aruna Attanayake <aruna470@gmail.com>
 * @version 1.2
 *
 * New phpseclib library, autoloader, stream etc.
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0
 */

class XSFtp extends CApplicationComponent
{
	/**
	 * Error codes
	 */
	const ERROR_NONE=0;
	const ERROR_CONNECTION_FAILED=1;

	/**
	 * @var string $host sftp host ip.
	 */
	public $host=null;

	/**
	 * @var string $port sftp host port default 22.
	 */
	public $port='22';

	/**
	 * @var string $username username of remote sftp account.
	 */
	public $username=null;

	/**
	 * @var string $username username of remote sftp account.
	 */
	public $password=null;

	/**
	 * @var integer the authentication error code. If there is an error, the error code will be non-zero.
	 * Defaults to 0, meaning no error.
	 */
	public $errorCode=self::ERROR_NONE;

	/**
	 * @var string the authentication error message. Defaults to empty.
	 */
	public $errorMessage;

	/**
	 * @var SFTP $objSftp sftp class object.
	 */
	private $objSftp=null;

	/**
	 * @var SSH2 $objSsh SSH class object.
	 */
	private $objSsh=null;

	/**
	 * @param string $host
	 * @param string $username
	 * @param string $password
	 * @param string $port
	 */
	public function __construct($host=null,$username=null,$password=null,$port='22')
	{
		$this->host=$host;
		$this->username=$username;
		$this->password=$password;
		$this->port=$port;
	}

	/**
	 * Initializes the component.
	 */
	public function init()
	{
		parent::init();

		// autoload phpseclib library
		set_include_path(dirname(__FILE__).'/phpseclib/'.get_include_path());
        Yii::registerAutoloader(array("XSFtp","autoload"));

        // register stream for sftp://
        Net_SFTP_Stream::register();
	}

	/**
	 * Initializes the component.
	 */
	public static function autoload($class)
	{
		$file = dirname(__FILE__).'/phpseclib/'.str_replace("_", DIRECTORY_SEPARATOR, $class) . '.php';
		if(is_file($file))
		{
			require_once($file);
			return true;
		}
		elseif($class === 'File_ASN1_Element')
		{
			require_once(dirname(__FILE__).'/phpseclib/File/ASN1.php');
			return true;
		}
		elseif($class === 'System_SSH_Agent')
		{
			require_once(dirname(__FILE__).'/phpseclib/System/SSH_Agent.php');
			return true;
		}
		return false;
	}

	/**
	 * Return sftp:// protocol handler that can be used with, for example, fopen(), dir(), etc.
	 * @param string $path the path to file on sftp server
	 * @return string stream url
	 */
	public function stream($path)
	{
		return "sftp://".$this->username.':'.$this->password.'@'.$this->host.$path;
	}

	/**
	 * Establish SFTP connection
	 * @return bool true when connection success
	 * @throws CException if connection fails
	 */
	public function connect()
	{
		$this->objSftp=new Net_SFTP($this->host);

		if($this->objSftp->login($this->username,$this->password))
		{
			$this->objSsh=new Net_SSH2($this->host);
			$this->objSsh->login($this->username,$this->password);
			return true;
		}
		else
		{
			$this->errorCode=self::ERROR_CONNECTION_FAILED;
			$this->errorMessage=Yii::t('XSFtp.sftp', 'SFtp connection failed!');
			return false;
		}
	}

	/**
	 * list directory contents
	 * @param string $directory Directory path
	 * @param bool $showHiddenFiles default false, if true list hidden files also
	 * @return array $files list of contents including directories
	 */
	public function listFiles($directory='.',$showHiddenfiles=false)
	{
		$res_files=$this->objSftp->nlist($directory);

		$files=array();

		foreach($res_files as $file)
		{
			if(!$showHiddenfiles&&('.'==$file||'..'==$file||'.'==$file[0]))
				continue;

			$files[]=$file;
		}

		return $files;
	}

	/**
	 * rawlist directory contents
	 * @param string $directory Directory path
	 * @param bool $showHiddenFiles default false, if true list hidden files also
	 * @return array $files list of contents including directories
	 */
	public function listFilesDetailed($directory='.',$showHiddenfiles=false)
	{
		$res_files=$this->objSftp->rawlist($directory);

		$files=array();

		foreach($res_files as $file=>$details)
		{
			if(!$showHiddenfiles&&('.'==$file||'..'==$file||'.'==$file[0]))
				continue;

			$details['filename'] = $file;
			$files[] = $details;
		}

		return $files;
	}

	/**
	 * Renames a file or a directory on the SFTP server
	 * @param string $oldname
	 * @param string $newname
	 * @return bool true if rename success
	 * @throws CException if rename fails
	 */
	public function rename($oldname, $newname)
	{
		if($this->objSftp->rename($oldname, $newname))
			return true;
		else
			throw new CException(Yii::t('XSFtp.sftp', 'Rename failed.'));
	}

	/**
	 * Create directory on sftp location
	 * @param string $directory Remote directory path
	 * @return bool true if directory creation success
	 * @throws CException if directory creation fails
	 */
	function createDirectory($directory)
	{
		if($this->objSftp->mkdir($directory))
			return true;
		else
			throw new CException(Yii::t('XSFtp.sftp', 'Directory creation failed.'));
	}

	/**
	 * Remove directory on sftp location
	 * @param string $directory Remote directory path
	 * @param bool $recursive If true remove directory even it is not empty
	 * @return bool true if directory removal success
	 * @throws CException if directory removal fails
	 */
	function removeDirectory($directory,$recursive=false)
	{
		if($recursive)
		{
			if($this->objSftp->delete($directory))
				return true;
			else
				throw new CException(Yii::t('XSFtp.sftp', 'Directory removal failed.'));
		}

		if($this->objSftp->rmdir($directory))
			return true;
		else
			throw new CException(Yii::t('XSFtp.sftp', 'Directory removal failed as folder is not empty.'));
	}

	/**
	 * Remove file on sftp location
	 * @param string $file Remote file path
	 * @return bool true if file removal success
	 * @throws CException if file removal fails
	 */
	function removeFile($file)
	{
		if($this->objSftp->delete($file))
			return true;
		else
			throw new CException(Yii::t('XSFtp.sftp', 'File removal failed.'));
	}

	/**
	 * Put file to a sftp location
	 * @param string $localFile Local file path
	 * @param string $remoteFile Remote file path or content
	 * @param integer $mode (1 - local file, 2 - string)
	 * @return bool true if file send success
	 * @throws CException if file transfer fails
	 */
	public function sendFile($localFile, $remoteFile, $mode=1)
	{
		if($this->objSftp->put($remoteFile, $localFile, $mode))
			return true;
		else
			throw new CException(Yii::t('XSFtp.sftp', 'File send failed.'));
	}

	/**
	 * Get file from sftp location
	 * @param string $remoteFile Remote file path
	 * @param string $localFile Local file path
	 * @return a string containing the contents of $remoteFile if $localFile is left undefined or a boolean false if
	 * the operation was unsuccessful.  If $localFile is defined, returns true or false depending on the success of the
	 * operation
	 * @throws CException if file transfer fails
	 */
	public function getFile($remoteFile, $localFile = false)
	{
		if($return=$this->objSftp->get($remoteFile, $localFile))
			return $return;
		else
			throw new CException(Yii::t('XSFtp.sftp', 'File get failed.'));
	}

	/**
	 * Returns the current directory
	 * @return string Current directory path
	 */
	public function getCurrentDir()
	{
		return $this->objSftp->pwd();
	}

	/**
	 * Check for directory
	 * @param string $directory Directory path
	 * @return bool true if is a directory otherwise false
	 */
	public function isDir($directory)
	{
		if($this->objSftp->chdir($directory))
		{
			$this->objSftp->chdir('..');
			return true;
		}

		return false;
	}

	/**
	 * Change directory
	 * @param string $directory Directory path
	 * @return bool true if directory change success
	 * @throws CException if directory change fails
	 */
	public function changeDirectory($directory)
	{
		if($this->objSftp->chdir($directory))
			return true;
		else
			throw new CException(Yii::t('XSFtp.sftp', 'Directory change failed.'));
	}

	/**
	 * Retreive file size
	 * @param string $file Remote file path
	 * @return string File size
	 */
	function getSize($file)
	{
		return $this->getFileStat($file,'size');
	}

	/**
	 * Retreive file attributes
	 * @param string $file Remote file path
	 * @param string $attribute Required attribute (size, gid, uid, atime, mtime, mode)
	 * @return string Attribute value
	 */
	private function getFileStat($file,$attribute)
	{
		$statinfo=$this->objSftp->stat($file);

		return $statinfo[$attribute];
	}

	/**
	 * Retreive file modified datetime
	 * @param string $file Remote file path
	 * @return string File modified timestamp
	 */
	function getMdtm($file)
	{
		return $this->getFileStat($file,'mtime');
	}

	/**
	 * Retreive file created datetime
	 * @param string $file Remote file path
	 * @return string File created timestamp
	 */
	function getAtime($file)
	{
		return $this->getFileStat($file,'atime');
	}

	/**
	 * Execute command on remote shell
	 * @param string $cmd Command ex:pwd
	 * @return string $output Command output
	 */
	function execCmd($cmd)
	{
		$output=$this->objSsh->exec($cmd);

		return $output;
	}
}
?>