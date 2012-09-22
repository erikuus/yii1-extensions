<?php
/**
 * XFileForm class file.
 *
 * This class contains methods that
 *  - translate filenames to reference codes and vice versa
 *  - get files from filesystem (storage system of NAE)
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0.0
 */
class XFileForm extends CFormModel
{
	protected $mntRoot = '/mnt';

	protected $allowedExtensions = array(
		'jpg','png','gif','tif','tiff'
	);

	protected $mapDirToCode = array(
		'eaa'=>'EAA',
		'era'=>'ERA',
		'efa'=>'EFA',
		'tla'=>'TLA',
		'lva'=>'LVVA',
		'amd'=>'AM',
		'erf'=>'ERAF',
		'ham'=>'HAMA',
		'lam'=>'LAMA',
		'lvm'=>'LVMA',
		'vam'=>'VAMA',
		'jom'=>'JOMA',
		'tam'=>'TAMA',
		'sam'=>'SAMA',
		'-0-'=>'-',
		'-1-'=>'/',
		'-2-'=>'_',
		'-3-'=>'.',
		'-4-'=>':',
		'-a-'=>'ä',
		'-o-'=>'ö',
		'-u-'=>'ü',
		'-d-'=>'õ',
		'-s-'=>'š',
		'-z-'=>'ž',
	);

	/**
	 * Convert directory path to reference code
	 * @param string $path path to directory (ex. "/mnt/saaga_laiendus/pargament/tla0230/1-0-ii/0000196")
	 * @return string reference code (ex. "TLA.230.1-II.196")
	 */
	public function pathToRefcode($path)
	{
		$pathParts=explode('/', $path);
		$pathPartsReverse=array_reverse($pathParts);

		$archiveFond = isset($pathPartsReverse[2]) ? $pathPartsReverse[2] : null;
		$inventory   = isset($pathPartsReverse[1]) ? $pathPartsReverse[1] : null;
		$volume      = isset($pathPartsReverse[0]) ? $pathPartsReverse[0] : null;

		return implode('.',array_filter(array(
			$this->dirToCode(strtolower(substr($archiveFond, 0, 3))),
			$this->dirToCode(substr($archiveFond, 3)),
			$this->dirToCode($inventory),
			$this->dirToCode($volume)
		)));
	}

	/**
	 * Convert reference code to directory path
	 * @param string reference code (ex. "TLA.230.1-II.196")
	 * @param string start path (ex. "/saaga_laiendus/pargament/")
	 * @return string $path partial directory path (ex. "/mnt/saaga_laiendus/pargament/tla0230/1-0-ii/0000196")
	 */
	public function refcodeToPath($refcode, $startPath=null)
	{
		$refcodeParts = explode('.', $refcode);

		// If refcode contains more than 3 points (ex. "EAA.T-1172.1.1-5.2.9")
		// we are hopelessly screwed
		if(count($refcodeParts)>4)
			return '';

		$archive=$this->codeToDir(strtoupper($refcodeParts[0]));
		$fond=$this->codeToDir($refcodeParts[1], '%04s');
		$inventory=$this->codeToDir($refcodeParts[2], '%03s');
		$volume=$this->codeToDir($refcodeParts[3], '%07s');

		// Not all filenames are lowercase as they should.
		// So we will try few variations
		// It is so ugly!!!
		$endPath1 = implode('/',array_filter(array($archive.$fond, $inventory, $volume)));
		$endPath2 = strtolower($endPath1);
		$endPath3 = implode('/',array_filter(array(strtoupper($archive).$fond, $inventory, $volume)));

		if (is_dir($this->mntRoot.$startPath.$endPath1))
			return $this->mntRoot.$startPath.$endPath1;
		elseif (is_dir($this->mntRoot.$startPath.$endPath2))
			return $this->mntRoot.$startPath.$endPath2;
		elseif (is_dir($this->mntRoot.$startPath.$endPath3))
			return $this->mntRoot.$startPath.$endPath3;
		else
			return '';
	}

	/**
	 * Get full paths to files filtered by given start path and page numbers
	 * @param string start path (ex. "/saaga_laiendus/pargament/")
	 * @param string reference code (ex. "TLA.230.1-II.196")
	 * @param string page number(s) (ex. "3,18-20,25")
	 * @return array of full paths to file
	 */
	public function getFilesByPage($startPath, $refcode, $pageNumberList=null)
	{
		$files = array();

		$fullPathToDir=$this->refcodeToPath($refcode, $startPath);
		$allFiles=$this->getFiles($fullPathToDir);

		$pageNumbers=$this->pageNumbersToArray($pageNumberList);

		foreach($allFiles as $file)
		{
			// get number segment
			$filenameParts = explode('_', $this->getFilename($file));
			$filenamePage = $filenameParts[3];

			// remove nulls
			$filenamePage=$this->trimNull($filenamePage);

			// In most cases page number part is followed by object part
			// (tla0230_001_bb-2-60-1-04_00011_x.tif),
			// but sometimes object part is missing
			// tla0230_001_BB-2-60-1-04_00011.png
			// so we need to remove extension (11.png -> 11)
			if(strstr($filenamePage,'.'))
				$filenamePage=strstr($filenamePage, '.', true); // as of PHP 5.3.0

			if($pageNumberList===null || in_array($filenamePage, $pageNumbers))
				$files[]=$file;
		}
		return $files;
	}

	/**
	 * Get full paths to files of given directory
	 * @param string $dir full path to directory
	 * @param boolean $natsort whether to natsort returned paths
	 * @return array full paths to files with allowed extension
	 */
	public function getFiles($dir, $natsort=true)
	{
		$files=array();

		$filenames=@scandir($dir);
		if($filenames)
		{
			$filenames=array_diff($filenames, array('.', '..'));
			foreach ($filenames as $filename)
			{
				$pathToFile=$dir.'/'.$filename;
				if (is_file($pathToFile))
				{
					if (in_array($this->getExtensionName($filename), $this->allowedExtensions))
						$files[]=$pathToFile;
				}
			}
		}

		if($natsort)
		{
			// We can not use natsort($files) as php generic
			// natsort fails with underscore and nulls
			// It results: _00001, _00002, _0001p
			// Correct is: _00001, _0001p, _00002
			$natsortFiles=array();
			foreach ($files as $file)
				$natsortFiles[$this->getPageNumber($file)]=$file;
			uksort($natsortFiles, "strnatcmp");
			return $natsortFiles;
		}
		else
			return $files;
	}

	/**
	 * Convert directory name to code
	 * @param string directory name (ex. "1-0-ii")
	 * @return string part of refcode (ex. "1-II")
	 */
	protected function dirToCode($dir)
	{
		return strtr($this->trimNull($dir), $this->mapDirToCode);
	}

	/**
	 * Convert code to directory name
	 * @param string part of refcode (ex. "1-II")
	 * @return string directory name (ex. "1-0-ii")
	 */
	protected function codeToDir($code, $format=false)
	{
		$dir=strtr($code, array_flip($this->mapDirToCode));
		return $format ? sprintf($format, $dir) : $dir;
	}

	/**
	 * Get filename from full path to file
	 * @param string path to file (ex. "/mnt/saaga_laiendus/saaga/eelk/eaa1168/001/0000001/eaa1168_001_0000001_00001_m.png")
	 * @return string the file name (ex. "eaa1168_001_0000001_00001_m.png")
	 */
	protected function getFilename($path)
	{
		if(($pos=strrpos($path,'/'))!==false)
			return substr($path,$pos+1);
		else
			return '';
	}

	/**
	 * Get page segment of page from full path to file. If object string is present, it is also returned
	 * @param string path to file (ex. "/mnt/saaga_laiendus/saaga/eelk/eaa1168/001/0000001/eaa1168_001_0000001_00001_m.png")
	 * @return string page number (ex. "1_m")
	 */
	protected function getPageNumber($path)
	{
		$parts = explode('_', $this->getFilename($path), 4);
		$page = $parts[3]; // ex. 00001_m.png

		if(strstr($page,'.'))
			$page=strstr($page, '.', true); // ex. 00001_m

		return $this->trimNull($page); // ex. 1_m
	}

	/**
	 * Get extension form filename
	 * @param string $filename
	 * @return string the file extension name for $filename.
	 * The extension name is in lowercase and does not include the dot character.
	 * An empty string is returned if filename does not have an extension name.
	 */
	protected function getExtensionName($filename)
	{
		if(($pos=strrpos($filename,'.'))!==false)
			return strtolower(substr($filename,$pos+1));
		else
			return '';
	}

	/**
	 * Trim "0" from left
	 * @param string
	 * @return trimmed string
	 */
	protected function trimNull($str)
	{
		if($str=ltrim($str, '0'))
			return $str;
		else
			return 0;
	}

	/**
	 * Convert list of page numbers to array
	 * @param $strPgn page numbers as list (ex. 3,18-20,25)
	 * @return array (ex. array(3,18,19,20,25))
	 */
	protected function pageNumbersToArray($strPgn)
	{
		$arrPgn = array();

		$arrComma = explode(',', $strPgn);
		foreach ($arrComma as $value)
		{
			if (strpos($value, '/') !== false)
			{
				$arrDubl = explode('/', $value);
				$arrPgn[] = trim($arrDubl[0]);
				$arrPgn[] = trim($arrDubl[1]);
			}
			elseif (strpos($value, '-') !== false)
			{
				$arrRange = explode('-', $value);
				$intRangeStart = trim($arrRange[0]);
				$intRangeEnd = trim($arrRange[1]);
				$intRangeAmount = $intRangeEnd - $intRangeStart;
				if (ctype_digit($intRangeStart) &&
					ctype_digit($intRangeEnd) &&
					$intRangeAmount > 0 &&
					$intRangeAmount < 200)
				{
					foreach (range($intRangeStart, $intRangeEnd) as $intRangeValue)
						$arrPgn[] = trim($intRangeValue);
				}
				else
				{
					$arrPgn[] = $intRangeStart;
					$arrPgn[] = $intRangeEnd;
				}
			}
			elseif($value!==null)
				$arrPgn[] = trim($value);
		}

		return $arrPgn;
	}
}