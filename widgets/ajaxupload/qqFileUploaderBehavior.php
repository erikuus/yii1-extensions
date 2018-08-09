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
	 * @param boolean $sanitizeFilename whether to sanitize filename
	 * @return array('success'=>true) or array('error'=>'error message')
	 */
	public function handleUpload($uploadDirectory,$saveAs=null,$replaceOldFile=false,$sanitizeFilename=false)
	{
		// check if file can be uploaded
		if(!is_writable($uploadDirectory))
			return array('error'=>"Server error. Upload directory isn't writable.");

		$this->checkFile();

		// get extension
		$pathinfo=pathinfo($this->file->getName());
		$ext=$pathinfo['extension'];

		// get filename
		$filename=$this->getFilename($saveAs,$sanitizeFilename);

		if($replaceOldFile===false)
		{
			while(file_exists($uploadDirectory.$filename.'.'.$ext))
				$filename.='-c';
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
	 * @param boolean $sanitizeFilename whether to sanitize filename
	 * @return array('success'=>true) or array('error'=>'error message')
	 */
	public function handleFtpUpload($ftp,$uploadDirectory,$saveAs=null,$sanitizeFilename=false)
	{
		// check if file can be uploaded
		$this->checkFile();

		// get filename
		$filename=$this->getFilename($saveAs,$sanitizeFilename);

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

	protected function getFilename($saveAs=null,$sanitizeFilename=false)
	{
		$pathinfo=pathinfo($this->file->getName());

		if($saveAs)
			$filename=$saveAs;
		elseif($sanitizeFilename)
			$filename=$this->sanitizeFilename($pathinfo['filename']);
		else
			$filename=preg_replace("/[^\w\x7F-\xFF\s]/i","",$pathinfo['filename']);

		if(!isset($filename) or empty($filename))
			$filename=uniqid();

		return $filename;
	}

	protected function sanitizeFilename($filename)
	{
		$sanitized=$this->removeAccents($filename); // Convert to ASCII

		// Standard replacements
		$invalid=array(
			' '=>'-',
			'%20'=>'-',
			'_'=>'-',
			'.'=>'-'
		);

		$sanitized=str_replace(array_keys($invalid),array_values($invalid),$sanitized);
		$sanitized=preg_replace('/[^A-Za-z0-9-]/','',$sanitized); // Remove all non-alphanumeric except .
		$sanitized=preg_replace('/-+/','-',$sanitized); // Replace any more than one - in a row
		$sanitized=str_replace('-.','.',$sanitized); // Remove last - if at the end

		return $sanitized;
	}

	protected function removeAccents($string)
	{
		if (!preg_match('/[\x80-\xff]/', $string))
			return $string;

		if (mb_detect_encoding($string, 'UTF-8', true))
		{
			$chars = array(
			// Decompositions for Latin-1 Supplement
			'ª' => 'a', 'º' => 'o',
			'À' => 'A', 'Á' => 'A',
			'Â' => 'A', 'Ã' => 'A',
			'Ä' => 'A', 'Å' => 'A',
			'Æ' => 'AE','Ç' => 'C',
			'È' => 'E', 'É' => 'E',
			'Ê' => 'E', 'Ë' => 'E',
			'Ì' => 'I', 'Í' => 'I',
			'Î' => 'I', 'Ï' => 'I',
			'Ð' => 'D', 'Ñ' => 'N',
			'Ò' => 'O', 'Ó' => 'O',
			'Ô' => 'O', 'Õ' => 'O',
			'Ö' => 'O', 'Ù' => 'U',
			'Ú' => 'U', 'Û' => 'U',
			'Ü' => 'U', 'Ý' => 'Y',
			'Þ' => 'TH','ß' => 's',
			'à' => 'a', 'á' => 'a',
			'â' => 'a', 'ã' => 'a',
			'ä' => 'a', 'å' => 'a',
			'æ' => 'ae','ç' => 'c',
			'è' => 'e', 'é' => 'e',
			'ê' => 'e', 'ë' => 'e',
			'ì' => 'i', 'í' => 'i',
			'î' => 'i', 'ï' => 'i',
			'ð' => 'd', 'ñ' => 'n',
			'ò' => 'o', 'ó' => 'o',
			'ô' => 'o', 'õ' => 'o',
			'ö' => 'o', 'ø' => 'o',
			'ù' => 'u', 'ú' => 'u',
			'û' => 'u', 'ü' => 'u',
			'ý' => 'y', 'þ' => 'th',
			'ÿ' => 'y', 'Ø' => 'O',
			// Decompositions for Latin Extended-A
			'Ā' => 'A', 'ā' => 'a',
			'Ă' => 'A', 'ă' => 'a',
			'Ą' => 'A', 'ą' => 'a',
			'Ć' => 'C', 'ć' => 'c',
			'Ĉ' => 'C', 'ĉ' => 'c',
			'Ċ' => 'C', 'ċ' => 'c',
			'Č' => 'C', 'č' => 'c',
			'Ď' => 'D', 'ď' => 'd',
			'Đ' => 'D', 'đ' => 'd',
			'Ē' => 'E', 'ē' => 'e',
			'Ĕ' => 'E', 'ĕ' => 'e',
			'Ė' => 'E', 'ė' => 'e',
			'Ę' => 'E', 'ę' => 'e',
			'Ě' => 'E', 'ě' => 'e',
			'Ĝ' => 'G', 'ĝ' => 'g',
			'Ğ' => 'G', 'ğ' => 'g',
			'Ġ' => 'G', 'ġ' => 'g',
			'Ģ' => 'G', 'ģ' => 'g',
			'Ĥ' => 'H', 'ĥ' => 'h',
			'Ħ' => 'H', 'ħ' => 'h',
			'Ĩ' => 'I', 'ĩ' => 'i',
			'Ī' => 'I', 'ī' => 'i',
			'Ĭ' => 'I', 'ĭ' => 'i',
			'Į' => 'I', 'į' => 'i',
			'İ' => 'I', 'ı' => 'i',
			'Ĳ' => 'IJ','ĳ' => 'ij',
			'Ĵ' => 'J', 'ĵ' => 'j',
			'Ķ' => 'K', 'ķ' => 'k',
			'ĸ' => 'k', 'Ĺ' => 'L',
			'ĺ' => 'l', 'Ļ' => 'L',
			'ļ' => 'l', 'Ľ' => 'L',
			'ľ' => 'l', 'Ŀ' => 'L',
			'ŀ' => 'l', 'Ł' => 'L',
			'ł' => 'l', 'Ń' => 'N',
			'ń' => 'n', 'Ņ' => 'N',
			'ņ' => 'n', 'Ň' => 'N',
			'ň' => 'n', 'ŉ' => 'n',
			'Ŋ' => 'N', 'ŋ' => 'n',
			'Ō' => 'O', 'ō' => 'o',
			'Ŏ' => 'O', 'ŏ' => 'o',
			'Ő' => 'O', 'ő' => 'o',
			'Œ' => 'OE','œ' => 'oe',
			'Ŕ' => 'R','ŕ' => 'r',
			'Ŗ' => 'R','ŗ' => 'r',
			'Ř' => 'R','ř' => 'r',
			'Ś' => 'S','ś' => 's',
			'Ŝ' => 'S','ŝ' => 's',
			'Ş' => 'S','ş' => 's',
			'Š' => 'S', 'š' => 's',
			'Ţ' => 'T', 'ţ' => 't',
			'Ť' => 'T', 'ť' => 't',
			'Ŧ' => 'T', 'ŧ' => 't',
			'Ũ' => 'U', 'ũ' => 'u',
			'Ū' => 'U', 'ū' => 'u',
			'Ŭ' => 'U', 'ŭ' => 'u',
			'Ů' => 'U', 'ů' => 'u',
			'Ű' => 'U', 'ű' => 'u',
			'Ų' => 'U', 'ų' => 'u',
			'Ŵ' => 'W', 'ŵ' => 'w',
			'Ŷ' => 'Y', 'ŷ' => 'y',
			'Ÿ' => 'Y', 'Ź' => 'Z',
			'ź' => 'z', 'Ż' => 'Z',
			'ż' => 'z', 'Ž' => 'Z',
			'ž' => 'z', 'ſ' => 's',
			// Decompositions for Latin Extended-B
			'Ș' => 'S', 'ș' => 's',
			'Ț' => 'T', 'ț' => 't',
			// Euro Sign
			'€' => 'E',
			// GBP (Pound) Sign
			'£' => '',
			// Vowels with diacritic (Vietnamese)
			// unmarked
			'Ơ' => 'O', 'ơ' => 'o',
			'Ư' => 'U', 'ư' => 'u',
			// grave accent
			'Ầ' => 'A', 'ầ' => 'a',
			'Ằ' => 'A', 'ằ' => 'a',
			'Ề' => 'E', 'ề' => 'e',
			'Ồ' => 'O', 'ồ' => 'o',
			'Ờ' => 'O', 'ờ' => 'o',
			'Ừ' => 'U', 'ừ' => 'u',
			'Ỳ' => 'Y', 'ỳ' => 'y',
			// hook
			'Ả' => 'A', 'ả' => 'a',
			'Ẩ' => 'A', 'ẩ' => 'a',
			'Ẳ' => 'A', 'ẳ' => 'a',
			'Ẻ' => 'E', 'ẻ' => 'e',
			'Ể' => 'E', 'ể' => 'e',
			'Ỉ' => 'I', 'ỉ' => 'i',
			'Ỏ' => 'O', 'ỏ' => 'o',
			'Ổ' => 'O', 'ổ' => 'o',
			'Ở' => 'O', 'ở' => 'o',
			'Ủ' => 'U', 'ủ' => 'u',
			'Ử' => 'U', 'ử' => 'u',
			'Ỷ' => 'Y', 'ỷ' => 'y',
			// tilde
			'Ẫ' => 'A', 'ẫ' => 'a',
			'Ẵ' => 'A', 'ẵ' => 'a',
			'Ẽ' => 'E', 'ẽ' => 'e',
			'Ễ' => 'E', 'ễ' => 'e',
			'Ỗ' => 'O', 'ỗ' => 'o',
			'Ỡ' => 'O', 'ỡ' => 'o',
			'Ữ' => 'U', 'ữ' => 'u',
			'Ỹ' => 'Y', 'ỹ' => 'y',
			// acute accent
			'Ấ' => 'A', 'ấ' => 'a',
			'Ắ' => 'A', 'ắ' => 'a',
			'Ế' => 'E', 'ế' => 'e',
			'Ố' => 'O', 'ố' => 'o',
			'Ớ' => 'O', 'ớ' => 'o',
			'Ứ' => 'U', 'ứ' => 'u',
			// dot below
			'Ạ' => 'A', 'ạ' => 'a',
			'Ậ' => 'A', 'ậ' => 'a',
			'Ặ' => 'A', 'ặ' => 'a',
			'Ẹ' => 'E', 'ẹ' => 'e',
			'Ệ' => 'E', 'ệ' => 'e',
			'Ị' => 'I', 'ị' => 'i',
			'Ọ' => 'O', 'ọ' => 'o',
			'Ộ' => 'O', 'ộ' => 'o',
			'Ợ' => 'O', 'ợ' => 'o',
			'Ụ' => 'U', 'ụ' => 'u',
			'Ự' => 'U', 'ự' => 'u',
			'Ỵ' => 'Y', 'ỵ' => 'y',
			// Vowels with diacritic (Chinese, Hanyu Pinyin)
			'ɑ' => 'a',
			// macron
			'Ǖ' => 'U', 'ǖ' => 'u',
			// acute accent
			'Ǘ' => 'U', 'ǘ' => 'u',
			// caron
			'Ǎ' => 'A', 'ǎ' => 'a',
			'Ǐ' => 'I', 'ǐ' => 'i',
			'Ǒ' => 'O', 'ǒ' => 'o',
			'Ǔ' => 'U', 'ǔ' => 'u',
			'Ǚ' => 'U', 'ǚ' => 'u',
			// grave accent
			'Ǜ' => 'U', 'ǜ' => 'u',
			);
			$string = strtr($string, $chars);
		}
		else
		{
			$chars = array();
			// Assume ISO-8859-1 if not UTF-8
			$chars['in'] = "\x80\x83\x8a\x8e\x9a\x9e"
				."\x9f\xa2\xa5\xb5\xc0\xc1\xc2"
				."\xc3\xc4\xc5\xc7\xc8\xc9\xca"
				."\xcb\xcc\xcd\xce\xcf\xd1\xd2"
				."\xd3\xd4\xd5\xd6\xd8\xd9\xda"
				."\xdb\xdc\xdd\xe0\xe1\xe2\xe3"
				."\xe4\xe5\xe7\xe8\xe9\xea\xeb"
				."\xec\xed\xee\xef\xf1\xf2\xf3"
				."\xf4\xf5\xf6\xf8\xf9\xfa\xfb"
				."\xfc\xfd\xff";

			$chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

			$string = strtr($string, $chars['in'], $chars['out']);
			$double_chars = array();
			$double_chars['in'] = array("\x8c", "\x9c", "\xc6", "\xd0", "\xde", "\xdf", "\xe6", "\xf0", "\xfe");
			$double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
			$string = str_replace($double_chars['in'], $double_chars['out'], $string);
		}
		return $string;
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