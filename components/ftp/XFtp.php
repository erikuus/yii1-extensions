<?php
/**
 * XFtp class file.
 *
 * XFtp handles FTP functionalities
 *
 * The following code is the component registration in the config file:
 *
 * <pre>
 * 'components'=>array(
 *     'ftp'=>array(
 *         'class'=>'ext.components.ftp.XFtp',
 *         'host'=>'127.0.0.1',
 *         'port'=>21,
 *         'username'=>'yourusername',
 *         'password'=>'yourpassword',
 *         'ssl'=>false,
 *         'timeout'=>90,
 *         'autoConnect'=>true,
 *     )
 * )
 * </pre>
 *
 * @link http://www.yiiframework.com/extension/ftp
 * @author Miles <cuiming2355_cn@hotmail.com>
 * @version 1.0
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0
 */
class XFtp extends CApplicationComponent
{
	/**
	 * Error codes
	 */
	const ERROR_NONE=0;
	const ERROR_CONNECTION_FAILED=1;
	const ERROR_LOGIN_FAILED=2;

	/**
	 * @var string the host for establishing FTP connection. Defaults to null.
	 */
	public $host=null;

	/**
	 * @var string the port for establishing FTP connection. Defaults to 21.
	 */
	public $port=21;

	/**
	 * @var string the username for establishing FTP connection. Defaults to null.
	 */
	public $username=null;

	/**
	 * @var string the password for establishing FTP connection. Defaults to null.
	 */
	public $password=null;

	/**
	 * @var boolean
	 */
	public $ssl=false;

	/**
	 * @var string the timeout for establishing FTP connection. Defaults to 90.
	 */
	public $timeout=90;

	/**
	 * @var boolean whether to turn passive mode on or off
	 */
	public $passiv=false;

	/**
	 * @var boolean whether the ftp connection should be automatically established
	 * the component is being initialized. Defaults to false. Note, this property is only
	 * effective when the XFtp object is used as an application component.
	 */
	public $autoConnect=true;
	/**
	 * @var integer the authentication error code. If there is an error, the error code will be non-zero.
	 * Defaults to 0, meaning no error.
	 */
	public $errorCode=self::ERROR_NONE;
	/**
	 * @var string the authentication error message. Defaults to empty.
	 */
	public $errorMessage;

	private $_active=false;
	private $_errors=null;
	private $_connection=null;

	/**
	 * @param	varchar	$host
	 * @param	varchar	$username
	 * @param	varchar	$password
	 * @param	boolean	$ssl
	 * @param	integer	$port
	 * @param	integer	$timeout
	 */
	public function __construct($host=null,$username=null,$password=null,$ssl=false,$port=21,$timeout=90)
	{
		$this->host=$host;
		$this->username=$username;
		$this->password=$password;
		$this->ssl=$ssl;
		$this->port=$port;
		$this->timeout=$timeout;
	}

	/**
	 * Close the FTP connection if the object is destroyed.
	 * @return	boolean
	 */
	public function __destruct()
	{
		return $this->close();
	}

	/**
	 * Initializes the component.
	 * This method is required by {@link IApplicationComponent} and is invoked by application
	 * when the XFtp is used as an application component.
	 * If you override this method, make sure to call the parent implementation
	 * so that the component can be marked as initialized.
	 */
	public function init()
	{
		parent::init();
		if($this->autoConnect)
			$this->setActive(true);
	}

	/**
	 * Open or close the FTP connection.
	 * @param boolean whether to open or close FTP connection
	 */
	public function setActive($value)
	{
		if($value!=$this->_active)
		{
			if($value)
				$this->connect();
			else
				$this->close();
		}
	}

	/**
	 * Return ftps:// protocol handler that can be used with, for example, fopen(), dir(), etc.
	 * @param string $path the path to file on sftp server
	 * @return string stream url
	 */
	public function stream($path)
	{
		return "ftp://".$this->username.':'.$this->password.'@'.$this->host.$path;
	}

	/**
	 * Connect to FTP if it is currently not
	 * @throws CException if connection fails
	 */
	public function connect()
	{
		if($this->_connection===null)
		{
			// Connect - SSL?
			$this->_connection=$this->ssl ? @ftp_ssl_connect($this->host,$this->port,$this->timeout) : @ftp_connect($this->host,$this->port,$this->timeout);

			// Check connection
			if(!$this->_connection)
			{
				$this->errorCode=self::ERROR_CONNECTION_FAILED;
				$this->errorMessage=Yii::t('XFtp.ftp', 'Ftp connection failed!');
				return false;
			}

			// Connection anonymous?
			if(!empty($this->username) and !empty($this->password))
				$login_result=@ftp_login($this->_connection, $this->username, $this->password);
			else
				$login_result=true;

			// Connection in passiv mode?
			if($this->passiv)
				ftp_pasv($this->_connection, true);

			// Check login
			if(!$login_result)
			{
				$this->errorCode=self::ERROR_LOGIN_FAILED;
				$this->errorMessage=Yii::t('XFtp.ftp', 'Ftp login failed!');
				return false;
			}

			$this->_active=true;
			return true;
		}
	}

	/**
	 * Closes the current FTP connection.
	 * @return boolean
	 */
	public function close()
	{
		if($this->_active)
		{
			// Close the connection
			if(ftp_close($this->_connection))
				return true;
			else
				return false;

			$this->_active=false;
			$this->_connection=null;
			$this->_errors=null;
		}
	}

	/**
	 * Passed an array of constants => values they will be set as FTP options.
	 * @param array $config
	 * @return object (chainable)
	 */
	public function setOptions($config)
	{
		if(!is_array($config))
			throw new CException(Yii::t('XFtp.ftp', 'The ftp config parameter must be passed an array!'));

		// Loop through configuration array
		foreach($config as $key=>$value)
		{
			// Set the options and test to see if they did so successfully - throw an exception if it failed
			if(!ftp_set_option($this->_connection,$key,$value))
				throw new CException(Yii::t('XFtp.ftp', 'The system failed to set the FTP option: "{key}" with the value: "{value}"', array('{key}'=>$key,'{key}'=>$value)));
		}
		return $this;
	}

	/**
	 * ListFiles executes a nlist command on the remote FTP server, returns an array of file names, false on failure.
	 * @param string remote directory
	 * @return mixed
	 */
	public function listFiles($directory)
	{
		return ftp_nlist($this->_connection, $directory);
	}

	/**
	 * ListFilesDetailed executes a rawlist command on the remote FTP server
	 * parses the data returned into an associative array and returns this array, false on failure.
	 * @param string remote directory
	 * @return mixed
	 */
	public function listFilesDetailed($directory)
	{
		if (is_array($res_files = ftp_rawlist($this->_connection, $directory)))
		{
			$files=array();

			foreach ($res_files as $file) {
				$chunks = preg_split("/\s+/", $file);
				list($details['rights'], $details['number'], $details['user'], $details['group'], $details['size'], $details['month'], $details['day'], $details['timeoryear'], $details['filename']) = $chunks;
				$details['mtime'] = $this->formatRawlistTime($details['month'], $details['day'], $details['timeoryear']);
				$details['type'] = $chunks[0]{0} === 'd' ? 2 : 1; // 2-directory, 1-file
				$files[] = $details;

			}
			return $files;
		}
	}

	/**
	 * Format time data received from ftp_rawlist
	 *
	 * NOTE!
	 * Ftp rawlist method can return timestamp in very unreliable way.
	 * For example when run in march 2015 it may return:
	 * Jan 02 07:14 (year missing, probably 2015)
	 * Mar 17 13:00 (year missing, probably 2015)
	 * Aug 19  2014 (time missing)
	 * Sep 30 02:00 (year missing, can't be 2015)
	 *
	 * @param string $month
	 * @param string $day
	 * @param string $timeOrYear
	 *
	 * @return integer unix timestamp
	 */
	protected function formatRawlistTime($month, $day, $timeOrYear)
	{
		if(strpos($timeOrYear, ':'))
		{
			$time=strtotime($day.' '.$month.' '.date('Y').' '.$timeOrYear);

			if($time > time())
				$time=strtotime($day.' '.$month.' '.(date('Y') -1).' '.$timeOrYear);
		}
		else
			$time=strtotime($day.' '.$month.' '.$timeOrYear) ;

		return $time;
	}

	/**
	 * Renames a file or a directory on the FTP server
	 * @param string $oldname
	 * @param string $newname
	 * @return bool true if rename success
	 * @throws CException if rename fails
	 */
	public function rename($oldname, $newname)
	{
		if(@ftp_rename($this->_connection, $oldname, $newname))
			return true;
		else
			throw new CException(Yii::t('XFtp.ftp', 'Rename failed.'));
	}

	/**
	 * Create directory on ftp location
	 * @param string $directory Remote directory path
	 * @return bool true if directory creation success
	 * @throws CException if directory creation fails
	 */
	public function createDirectory($directory)
	{
		// if directory already exists or can be immediately created return true
		if($this->checkDirectory($directory) || @ftp_mkdir($this->_connection, $directory))
			return true;

		// otherwise recursively try to make the directory
		if(!$this->createDirectory(dirname($directory)))
			return false;

		if(@ftp_mkdir($this->_connection, $directory))
			return true;
		else
			throw new CException(Yii::t('XFtp.ftp', 'Directory creation failed.'));
	}

	/**
	 * Check whether directory already exists
	 * @param string $dir ftp dir name
	 * @return boolean whether exists
	 */
	protected function checkDirectory($directory)
	{
		// get current directory
		$currentDir=$this->currentDir();

		// test if you can change directory to $directory
		if($this->changeDirectory($directory))
		{
			// If it is a directory, then change the directory back to the original directory
			$this->changeDirectory($currentDir);
			return true;
		}
		else
			return false;
	}

	/**
	 * Remove directory on ftp location
	 * @param string $directory Remote directory path
	 * @param bool $recursive If true remove directory even it is not empty
	 * @return bool true if directory removal success
	 * @throws CException if directory removal fails
	 */
	public function removeDirectory($directory, $recursive=false)
	{
		if($recursive)
			return $this->recursiveRemoveDirectory($directory);

		if(@ftp_rmdir($this->_connection, $directory))
			return true;
		else
			throw new CException(Yii::t('XFtp.ftp', 'Directory removal failed as folder is not empty.'));
	}

	/**
	 * Remove directory on ftp location recursively
	 * @param string $directory Remote directory path
	 * @return bool true if directory removal success
	 * @throws CException if directory removal fails
	 */
	public function recursiveRemoveDirectory($directory)
	{
	    // here we attempt to delete the file/directory
	    if(!(@ftp_rmdir($this->_connection, $directory) || @ftp_delete($this->_connection, $directory)) )
	    {
	        // if the attempt to delete fails, get the file listing
	        $filelist = @ftp_nlist($this->_connection, $directory );

	        // loop through the file list and recursively delete the FILE in the list
	        foreach ($filelist as $file)
	            $this->recursiveRemoveDirectory($file);

	        // if the file list is empty, delete the DIRECTORY we passed
			if(@ftp_rmdir($this->_connection, $directory))
				return true;
			else
				throw new CException(Yii::t('XFtp.ftp', 'Directory removal failed.'));
	    }
	}

	/**
	 * Remove file on ftp location
	 * @param string $file Remote file path
	 * @return bool true if file removal success
	 * @throws CException if file removal fails
	 */
	public function removeFile($file)
	{
		if(@ftp_delete($this->_connection,$file))
			return true;
		else
			throw new CException(Yii::t('XFtp.ftp', 'File removal failed.'));
	}

	/**
	 * Executes a put command on the remote FTP server.
	 * @param string $localFile Local file path
	 * @param string $remoteFile Remote file path or content
	 * @param const mode
	 * @return bool true if file send success
	 * @throws CException if file transfer fails
	 */
	public function sendFile($localFile, $remoteFile, $mode=FTP_BINARY)
	{
		if(@ftp_put($this->_connection, $remoteFile, $localFile, $mode))
			return true;
		else
			throw new CException(Yii::t('XFtp.ftp', 'File send failed.'));
	}

	/**
	 * Get file from ftp location
	 * @param string $remoteFile Remote file path
	 * @param string $localFile Local file path
	 * @param const mode
	 * @return a string containing the contents of $remoteFile if $localFile is left undefined or a boolean false if
	 * the operation was unsuccessful.  If $localFile is defined, returns true or false depending on the success of the
	 * operation
	 * @throws CException if file transfer fails
	 */
	public function getFile($remoteFile, $localFile=false, $mode=FTP_BINARY)
	{
		if($localFile)
		{
			if(@ftp_get($this->_connection, $localFile, $remoteFile, $mode))
				return $remote;
			else
				throw new CException(Yii::t('XFtp.ftp', 'File get failed.'));
		}
		else
		{
			ob_start();
			if(ftp_get($this->_connection, "php://output", $remoteFile, $mode))
			{
				$remote = ob_get_contents();
				ob_end_clean();
				return $remote;
			}
			else
			{
				ob_end_clean();
				throw new CException(Yii::t('XFtp.ftp', 'File get failed.'));
			}
		}
	}

	/**
	 * Returns the name of the current working directory.
	 * @return string
	 */
	public function currentDir()
	{
		return ftp_pwd($this->_connection);
	}

	/**
	 * Changes to the parent directory on the remote FTP server.
	 * @return boolean
	 */
	public function parentDir()
	{
		if(@ftp_cdup($this->_connection))
			return true;
		else
			return false;
	}

	/**
	 * Change the current working directory on the remote FTP server.
	 * @param string remote directory
	 * @return boolean
	 * @throws CException if directory change fails
	 */
	public function changeDirectory($directory)
	{
		if(@ftp_chdir($this->_connection, $directory))
			return true;
		else
			return false;
	}

	/**
	 * Returns the size of the given file
	 * Note: Not all servers support this feature!
	 * @param string remote file
	 * @return mixed Returns the file size on success, or false on error.
	 */
	public function getSize($file)
	{
		$buff=ftp_size($this->_connection, $file);
		if($buff!=-1)
			return $buff;
		else
			throw false;
	}

	/**
	 * Returns the last modified time of the given file
	 * Note: Not all servers support this feature!
	 * Note: mdtm method does not work with directories.
	 * @param string remote file
	 * @return mixed Returns the last modified time as a Unix timestamp on success, or false on error.
	 */
	public function getMdtm($file)
	{
		$buff=ftp_mdtm($this->_connection, $file);
		if($buff!=-1)
			return $buff;
		else
			return false;
	}

	/**
	 * Execute a remote command on the FTP server.
	 * @see	http://us2.php.net/manual/en/function.ftp-exec.php
	 * @param string remote command
	 * @return boolean
	 */
	public function execCmd($command)
	{
		if(ftp_exec($this->_connection, $command))
			return true;
		else
			return false;
	}

	/**
	 * Permissions executes a chmod command on the remote FTP server.
	 *
	 * @param string remote file
	 * @param mixed  mode
	 * @return boolean
	 */
	public function chmod($file, $mode)
	{
		if(ftp_chmod($this->_connection, $mode, $file))
			return true;
		else
			return false;
	}
}