<?php
/**
 * XS3 class file.
 *
 * XS3 is a wrapper for the S3.php class provided by Donovan Schönknecht (@link http://undesigned.org.za/2007/10/22/amazon-s3-php-class)
 *
 * The following shows how to use XS3 component.
 *
 * Configure (config/main.php)
 * <pre>
 * 'components'=>array(
 *     's3'=>array(
 *          'class'=>'ext.components.s3.XS3',
 *          'accessKey'=>'my_access_key',
 *          'secretKey'=>'my_secret_key',
 *     ),
 * )
 * </pre>
 *
 * Send file in controller
 * <pre>
 * $s3=Yii::app()->s3;
 * $s3->bucket='myBucket';
 * $success=$s3->send('/path/to/local/file.jpg', '/location/in/bucket/662a9a5f-4dc1-4dab-cf72-69f7eae3fb0a.jpg')
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */

require_once dirname(dirname(__FILE__)).'/s3/S3.php';

class XS3 extends CApplicationComponent
{
	/**
	 * @var string amazon s3 access key
	 */
	public $accessKey;
	/**
	 * @var string amazon s3 secret key
	 */
	public $secretKey;
	/**
	 * @var string amazon s3 default bucket name
	 */
	public $bucket;

	private $_s3Class;

	/**
	 * Initializes the application component.
	 */
	public function init()
	{
		parent::init();

		if(!$this->accessKey || !$this->secretKey)
			throw new CException('S3 keys are not set.');

		$this->_s3Class=new S3($this->accessKey, $this->secretKey);
	}

	/**
	 * Send file to s3
	 * @param string $localFile the path to local file
	 * @param string $s3File the path on destination
	 * @param boolean $deleteLocal whether to delete local file after
	 * it was successfully sent to s3 storage. Defaults to true.
	 * @return boolean whether file was successfully sent
	 */
	public function send($localFile, $s3File, $deleteLocal=true)
	{
		if(!$this->bucket)
			throw new CException('S3 bucket is not set.');

		if ($this->_s3Class->putObjectFile($localFile, $this->bucket, $s3File, S3::ACL_PUBLIC_READ))
		{
			if($deleteLocal===true)
				unlink($localFile);

			return true;
		}
		else
			return false;
	}

	/**
	 * @param string $original File to upload - can be any valid CFile filename
	 * @param string $uploaded Name of the file on destination -- can include directory separators
	 */
	public function upload($original,$uploaded="",$bucket="")
	{
		$s3=$this->getInstance();

		if($bucket=="")
			$bucket=$this->bucket;

		if($bucket===NULL||trim($bucket)=="")
			throw new CException('Bucket param cannot be empty');

		$file=Yii::app()->file->set($original);

		if(!$file->exists)
			throw new CException('Origin file not found');

		$fs1=$file->size;

		if(!$fs1)
		{
			$this->lastError="Attempted to upload empty file.";
			return false;
		}

		if(trim($uploaded)=="")
			$uploaded=$original;

		//if (!$s3->putObject($s3->inputResource(fopen($file->getRealPath(), 'r'), $fs1), $bucket, $uploaded, S3::ACL_PUBLIC_READ))
		echo $file->getRealPath();
		//if (!$s3->putObject($s3->inputResource( fopen($file->getRealPath(), 'rb'), $fs1), $bucket, $uploaded, S3::ACL_PUBLIC_READ))
		if(!$s3->putObjectFile($original,$bucket,$uploaded,S3::ACL_PUBLIC_READ))
		{
			$this->lastError="Unable to upload file.";
			return false;
		}
		return true;
	}

	/**
	 * Calls the named method which is not a class method.
	 * @param string $name the method name
	 * @param array $params method parameters
	 * @return mixed the method return value
	 */
	public function __call($name,$params)
	{
		if(method_exists($this->_s3Class,$name))
			return call_user_func_array(array($this->_s3Class,$name),$params);

		return parent::__call($name,$params);
	}
}
?>