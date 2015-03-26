<?php

/**
 * Handle file uploads
 */
class qqFileUploaderBehavior extends CBehavior
{
	public $allowedExtensions=array();
	public $sizeLimit=10485760;

	protected $file;

	/**
	 * Init file upload
	 * @param array $allowedExtensions the list of allowed extensions
	 * @param integer $sizeLimit the file size limit in bytes
	 */
	public function initUpload($allowedExtensions=array(),$sizeLimit=null)
	{
		// set limits
		$this->allowedExtensions=$allowedExtensions;
		$this->sizeLimit=$sizeLimit;

		// check server limits
		$this->checkServerSettings();

		// get file
		if(isset($_GET['qqfile']))
			$this->file=new qqUploadedFileXhr();
		elseif(isset($_FILES['qqfile']))
			$this->file=new qqUploadedFileForm();
		else
			$this->file=false;
	}

	/**
	 * Handle file upload
	 * @param string $uploadDirectory the path to directory where files are uploaded
	 * @param string $saveAs the new file name
	 * @param boolean $replaceOldFile whether to replace existing files
	 * @return array('success'=>true) or array('error'=>'error message')
	 */
	public function handleUpload($uploadDirectory,$saveAs=null,$replaceOldFile=false)
	{
		// check if file can be uploaded
		if(!is_writable($uploadDirectory))
			return array('error'=>"Server error. Upload directory isn't writable.");

		$this->checkFile();

		// get extension
		$pathinfo=pathinfo($this->file->getName());
		$ext=$pathinfo['extension'];

		// get filename
		$filename=$this->getFilename($saveAs);

		if($replaceOldFile===false)
		{
			while(file_exists($uploadDirectory.$filename.'.'.$ext))
				$filename.=rand(10,99);
		}

		// upload file
		if($this->file->save($uploadDirectory.$filename.'.'.$ext))
			return array('success'=>true,'filename'=>$filename.'.'.$ext);
		else
			return array('error'=>'Could not save uploaded file.'.'The upload was cancelled, or server error encountered');
	}

	/**
	 * Handle ftp or sftp file upload
	 * @param CApplicationComponent $sftp the XFtp or XSFtp extension
	 * @param string $uploadDirectory the path to directory where files are uploaded
	 * @param string $saveAs the new file name
	 * @return array('success'=>true) or array('error'=>'error message')
	 */
	public function handleFtpUpload($ftp, $uploadDirectory, $saveAs=null)
	{
		// check if file can be uploaded
		$this->checkFile();

		// get filename
		$filename=$this->getFilename($saveAs);

		// get extension
		$pathinfo=pathinfo($this->file->getName());
		$ext=$pathinfo['extension'];

		// upload file
		if($this->file->save($ftp->stream($uploadDirectory.$filename.'.'.$ext)))
			return array('success'=>true,'filename'=>$filename.'.'.$ext);
		else
			return array('error'=>'Could not save uploaded file.'.'The upload was cancelled, or server error encountered');
	}

	protected function checkServerSettings()
	{
		$postSize=$this->toBytes(ini_get('post_max_size'));
		$uploadSize=$this->toBytes(ini_get('upload_max_filesize'));

		if($postSize<$this->sizeLimit||$uploadSize<$this->sizeLimit)
		{
			$size=max(1,$this->sizeLimit/1024/1024).'M';
			die("{'error':'increase post_max_size and upload_max_filesize to $size'}");
		}
	}

	protected function checkFile()
	{
		if(!$this->file)
			return array('error'=>'No files were uploaded.');

		$size=$this->file->getSize();

		if($size==0)
			return array('error'=>'File is empty');

		if($size>$this->sizeLimit)
			return array('error'=>'File is too large');

		$pathinfo=pathinfo($this->file->getName());
		$ext=$pathinfo['extension'];

		if($this->allowedExtensions!==array()&&!in_array(strtolower($ext),$this->allowedExtensions))
		{
			$these=implode(', ',$this->allowedExtensions);
			return array('error'=>'File has an invalid extension, it should be one of '.$these.'.');
		}
	}

	protected function getFilename($saveAs=null)
	{
		$pathinfo=pathinfo($this->file->getName());

		if($saveAs)
			$filename=$saveAs;
		else
			$filename=preg_replace("/[^\w\x7F-\xFF\s]/i","",$pathinfo['filename']);

		if(!isset($filename) or empty($filename))
			$filename=uniqid();

		return $filename;
	}

	protected function toBytes($str)
	{
		$val=trim($str);
		$last=strtolower($str[strlen($str)-1]);
		switch($last)
		{
			case 'g':
				$val*=1024;
			case 'm':
				$val*=1024;
			case 'k':
				$val*=1024;
		}
		return $val;
	}
}

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr
{
	function save($path)
	{
		$input=fopen("php://input","r");
		$temp=tmpfile();
		$realSize=stream_copy_to_stream($input,$temp);
		fclose($input);

		if($realSize!=$this->getSize())
			return false;

		$target=fopen($path,"w");

		fseek($temp,0,SEEK_SET);
		stream_copy_to_stream($temp,$target);
		fclose($target);

		return true;
	}

	function getName()
	{
		return $_GET['qqfile'];
	}

	function getSize()
	{
		if(isset($_SERVER["CONTENT_LENGTH"]))
			return (int)$_SERVER["CONTENT_LENGTH"];
		else
			throw new Exception('Getting content length is not supported.');
	}
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm
{

	function save($path)
	{
		if(!move_uploaded_file($_FILES['qqfile']['tmp_name'],$path))
			return false;

		return true;
	}

	function getName()
	{
		return $_FILES['qqfile']['name'];
	}

	function getSize()
	{
		return $_FILES['qqfile']['size'];
	}
}