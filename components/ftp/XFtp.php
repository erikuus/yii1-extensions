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
 * See the following code example:
 *
 * <pre>
 * $ftp = Yii::app()->ftp;
 * $ftp->put('remote.txt', 'D:\local.txt');
 * $ftp->rmdir('exampledir');
 * $ftp->chdir('aaa');
 * $ftp->currentDir();
 * $ftp->delete('remote.txt');
 * </pre>
 *
 * @link http://www.yiiframework.com/extension/ftp
 * @author Miles <cuiming2355_cn@hotmail.com>
 *
 * function listFilesDetailed($directory)
 * @author Erik Uus <erik.uus@gmail.com>
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
	 * @return boolean whether the FTP connection is established
	 */
	public function getActive()
	{
		return $this->_active;
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
	 *
	 * @return	boolean
	 */
	public function close()
	{
		if($this->getActive())
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
	 *
	 * @param	array	$config
	 * @return	object (chainable)
	 */
	public function setOptions($config)
	{
		if($this->getActive())
		{
			if(!is_array($config))
				throw new CException('Ftp Error: The config parameter must be passed an array!');

			// Loop through configuration array
			foreach($config as $key=>$value)
			{
				// Set the options and test to see if they did so successfully - throw an exception if it failed
				if(!ftp_set_option($this->_connection,$key,$value))
					throw new CException('Ftp Error: The system failed to set the FTP option: "'.$key.'" with the value: "'.$value.'"');
			}
			return $this;
		}
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * Execute a remote command on the FTP server.
	 *
	 * @see		http://us2.php.net/manual/en/function.ftp-exec.php
	 * @param	string remote command
	 * @return	boolean
	 */
	public function execute($command)
	{
		if($this->getActive())
		{
			// Execute command
			if(ftp_exec($this->_connection,$command))
				return true;
			else
				return false;
		}
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * Get executes a get command on the remote FTP server.
	 *
	 * @param	string local file
	 * @param	string remote file
	 * @param	const  mode
	 * @return	boolean
	 */
	public function get($local,$remote,$mode=FTP_ASCII)
	{
		if($this->getActive())
		{
			// Get the requested file
			if(ftp_get($this->_connection,$local,$remote,$mode))
			{
				// If successful, return the path to the downloaded file...
				return $remote;
			}
			else
				return false;
		}
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * Put executes a put command on the remote FTP server.
	 *
	 * @param	string remote file
	 * @param	string local file
	 * @param	const  mode
	 * @return	boolean
	 */
	public function put($remote,$local,$mode=FTP_ASCII)
	{
		if($this->getActive())
		{
			// Upload the local file to the remote location specified
			if(ftp_put($this->_connection,$remote,$local,$mode))
				return true;
			else
				return false;
		}
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * Rename executes a rename command on the remote FTP server.
	 *
	 * @param	string old filename
	 * @param	string new filename
	 * @return	boolean
	 */
	public function rename($old,$new)
	{
		if($this->getActive())
		{
			// Rename the file
			if(ftp_rename($this->_connection,$old,$new))
				return true;
			else
				return false;
		}
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * Rmdir executes an rmdir (remove directory) command on the remote FTP server.
	 *
	 * @param	string remote directory
	 * @return	boolean
	 */
	public function rmdir($dir)
	{
		if($this->getActive())
		{
			// Remove the directory
			if(ftp_rmdir($this->_connection,$dir))
				return true;
			else
				return false;
		}
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * Mkdir executes an mkdir (create directory) command on the remote FTP server.
	 *
	 * @param	string remote directory
	 * @return	boolean
	 */
	public function mkdir($dir)
	{
		if($this->getActive())
		{
			// create directory
			if(ftp_mkdir($this->_connection,$dir))
				return true;
			else
				return false;
		}
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * Returns the last modified time of the given file
	 * Note: Not all servers support this feature!
	 * Note: mdtm method does not work with directories.
	 *
	 * @param	string remote file
	 * @return	mixed Returns the last modified time as a Unix timestamp on success, or false on error.
	 */
	public function mdtm($file)
	{
		if($this->getActive())
		{
			// get the last modified time
			$buff=ftp_mdtm($this->_connection,$file);
			if($buff!=-1)
				return $buff;
			else
				return false;
		}
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * Returns the size of the given file
	 * Note: Not all servers support this feature!
	 *
	 * @param	string remote file
	 * @return	mixed Returns the file size on success, or false on error.
	 */
	public function size($file)
	{
		if($this->getActive())
		{
			// get the size of $file
			$buff=ftp_size($this->_connection,$file);
			if($buff!=-1)
				return $buff;
			else
				return false;
		}
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * Remove executes a delete command on the remote FTP server.
	 *
	 * @param	string remote file
	 * @return	boolean
	 */
	public function delete($file)
	{
		if($this->getActive())
		{
			// Delete the specified file
			if(ftp_delete($this->_connection,$file))
				return true;
			else
				return false;
		}
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * Change the current working directory on the remote FTP server.
	 *
	 * @param	string remote directory
	 * @return	boolean
	 */
	public function chdir($dir)
	{
		if($this->getActive())
		{
			// Change directory
			if(ftp_chdir($this->_connection,$dir))
				return true;
			else
				return false;
		}
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * Changes to the parent directory on the remote FTP server.
	 *
	 * @return	boolean
	 */
	public function parentDir()
	{
		if($this->getActive())
		{
			// Move up!
			if(ftp_cdup($this->_connection))
				return true;
			else
				return false;
		}
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * Returns the name of the current working directory.
	 *
	 * @return	string
	 */
	public function currentDir()
	{
		if($this->getActive())
			return ftp_pwd($this->_connection);
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * Permissions executes a chmod command on the remote FTP server.
	 *
	 * @param	string remote file
	 * @param	mixed  mode
	 * @return	boolean
	 */
	public function chmod($file,$mode)
	{
		if($this->getActive())
		{
			// Change the desired file's permissions
			if(ftp_chmod($this->_connection,$mode,$file))
				return true;
			else
				return false;
		}
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * ListFiles executes a nlist command on the remote FTP server, returns an array of file names, false on failure.
	 *
	 * @param	string remote directory
	 * @return	mixed
	 */
	public function listFiles($directory)
	{
		if($this->getActive())
			return ftp_nlist($this->_connection,$directory);
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * ListFilesDetailed executes a rawlist command on the remote FTP server
	 * parses the data returned into an associative array and returns this array, false on failure.
	 *
	 * @param	string remote directory
	 * @return	mixed
	 */
	function listFilesDetailed($directory)
	{
		if($this->getActive())
		{
			if (is_array($res_files = ftp_rawlist($this->_connection, $directory)))
			{
				$files=array();

				foreach ($res_files as $file) {
					$chunks = preg_split("/\s+/", $file);
					list($details['rights'], $details['number'], $details['user'], $details['group'], $details['size'], $details['month'], $details['day'], $details['time'], $details['filename']) = $chunks;
					$details['mtime'] = strtotime(implode(' ', array($details['month'],$details['day'],$details['time'])));
					$details['type'] = $chunks[0]{0} === 'd' ? 2 : 1; // 2-directory, 1-file
					$files[] = $details;

				}
				return $files;
			}
		}
		else
			throw new CException('Ftp is inactive and cannot perform any operations.');
	}

	/**
	 * Close the FTP connection if the object is destroyed.
	 *
	 * @return	boolean
	 */
	public function __destruct()
	{
		return $this->close();
	}
}