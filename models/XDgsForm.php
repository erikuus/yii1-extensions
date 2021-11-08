<?php
/**
 * XDgsForm class file.
 *
 * This class contains methods that make sql queries to kmoodul database (dgs. schema) using Yii DAO
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0.0
 */
class XDgsForm extends CFormModel
{
	const APP_SAAGA=1;
	const APP_KAART=2;
	const APP_PARGAMENT=3;
	const APP_PITSER=4;
	const APP_FOTIS=5;

	const STATUS_PUBLIC=1;
	const STATUS_DISCONNECTED=2;
	const STATUS_INVISIBLE=3;
	const STATUS_FORBIDDEN=4;

	/**
	 * @param string item reference cod
	 * @return boolean if item is public
	 */
	public function isItemAvailable($reference)
	{
		$reference = $this->quote($reference);
		$statuses = implode(', ', array(self::STATUS_PUBLIC, self::STATUS_FORBIDDEN));
		$sql = "SELECT id FROM  dgs.tbl_app WHERE refcode={$reference} AND status IN ($statuses)";
		$id = Yii::app()->kmooduldb->createCommand($sql)->queryScalar();
		return $id ? true : false;
	}

	/**
	 * @param array of item reference codes
	 * @return array of public item references
	 *
	 * Example:
	 * Array
	 * (
	 *   [0] => ERAF.24.1.281
	 *   [1] => ERA.2280.1.1
	 *   [2] => EAA.1427.1.24
	 *   [3] => EAA.2072.3.320
	 *   [4] => ERA.1608.2.2260
	 * )
	 *
	 */
	public function getAvailableItems($arrReference)
	{
		array_walk($arrReference, array($this, 'walkQuote'));
		$list = implode(',',$arrReference);
		$statuses = implode(', ', array(self::STATUS_PUBLIC, self::STATUS_FORBIDDEN));
		$sql = "SELECT DISTINCT(refcode) FROM  dgs.tbl_app WHERE refcode IN ($list) AND status IN ($statuses)";
		return Yii::app()->kmooduldb->createCommand($sql)->queryColumn();
	}

	/**
	 * @param string item reference code
	 * @return boolean if parent is public
	 */
	public function isParentPublic($reference)
	{
		$reference = $this->quote($reference.'.%');
		$sql = "SELECT COUNT(*) FROM  dgs.tbl_app WHERE refcode LIKE {$reference} AND status=".self::STATUS_PUBLIC;
		$c = Yii::app()->kmooduldb->createCommand($sql)->queryScalar();
		return $c > 0 ? true : false;
	}

	/**
	 * @param string item reference code
	 * @return boolean if item is public
	 */
	public function isItemPublic($reference)
	{
		$reference=$this->quote($reference);
		$sql = "SELECT id FROM  dgs.tbl_app WHERE refcode={$reference} AND status=".self::STATUS_PUBLIC;
		$id = Yii::app()->kmooduldb->createCommand($sql)->queryScalar();
		return $id ? true : false;
	}

	/**
	 * @param array of item reference codes
	 * @return array of public item references
	 *
	 * Example:
	 * Array
	 * (
	 *   [0] => ERAF.24.1.281
	 *   [1] => ERA.2280.1.1
	 *   [2] => EAA.1427.1.24
	 *   [3] => EAA.2072.3.320
	 *   [4] => ERA.1608.2.2260
	 * )
	 *
	 */
	public function getPublicItems($arrReference)
	{
		array_walk($arrReference, array($this, 'walkQuote'));
		$list=implode(',',$arrReference);
		$sql = "SELECT DISTINCT(refcode) FROM  dgs.tbl_app WHERE refcode IN ($list) AND status=".self::STATUS_PUBLIC;
		return Yii::app()->kmooduldb->createCommand($sql)->queryColumn();
	}

	/**
	 * @param string item reference code
	 * @return array item data
	 *
	 * Example:
	 * Array
	 * (
	 *   [0]=>Array
	 *   (
	 *     ["refcode"]=>eaa.1.2.357
	 *     ["appcode"]=>Saaga
	 *     ["status"]=>Public
	 *   )
	 *   [1]=>Array
	 *   (
	 *     ["refcode"]=>eaa.1.2.357
	 *     ["appcode"]=>Maps
	 *     ["status"]=>Public
	 *   )
	 * )
	 *
	 */
	public function getItem($reference)
	{
		$reference=$this->quote($reference);
		$sql = "SELECT refcode, appcode, status FROM  dgs.tbl_app WHERE refcode=$reference";
		$data = Yii::app()->kmooduldb->createCommand($sql)->queryAll();
		array_walk_recursive($data, array($this, 'walkFormat'));
		return $data;
	}

	/**
	 * @param array of item reference codes
	 * @return array of items data
	 *
	 * Example:
	 * Array
	 * (
	 *   [0]=>Array
	 *   (
	 *     ["refcode"]=>eaa.1.2.357
	 *     ["appcode"]=>Saaga
	 *     ["status"]=>Public
	 *   )
	 *   [1]=>Array
	 *   (
	 *     ["refcode"]=>era.1608.2.2260
	 *     ["appcode"]=>Saaga
	 *     ["status"]=>Forbidden
	 *   )
	 * )
	 *
	 */
	public function getItems($arrReference)
	{
		array_walk($arrReference, array($this, 'walkQuote'));
		$list=implode(',',$arrReference);
		$sql = "SELECT refcode, appcode, status FROM  dgs.tbl_app WHERE refcode IN ($list) ORDER BY refcode";
		$data = Yii::app()->kmooduldb->createCommand($sql)->queryAll();
		array_walk_recursive($data, array($this, 'walkFormat'));
		return $data;
	}

	/**
	 * @param string item reference code
	 * @return array directories
	 *
	 * Example:
	 * Array
	 * (
	 *   [0]=>/mnt/saaga_laiendus/saaga/tla/tla0230/001/BB-2-60-1-04
	 *   [1]=>/mnt/saaga_laiendus/pargament/tla0230/001/bb-2-60-1-04
	 * )
	 *
	 */
	public function getDirectories($reference)
	{
		$reference=$this->quote($reference);
		$sql = "SELECT directory FROM  dgs.tbl_mnt WHERE refcode=$reference";
		return Yii::app()->kmooduldb->createCommand($sql)->queryColumn();
	}

	/**
	 * @param string root directory path
	 * @return array directories
	 *
	 * Example:
	 * Array
	 * (
	 *   [0]=>/mnt/saaga_laiendus/pargament/eaa2069/002/0000147
	 *   [1]=>/mnt/saaga_laiendus/pargament/eaa2069/002/0000148
	 * )
	 *
	 */
	public function getNewDirectories($root)
	{
		$root=$this->quote($root);
		$sql = "SELECT directory FROM  dgs.tbl_mnt as t WHERE position($root in directory)=1 AND not exists (select 1 from dgs.tbl_app where t.refcode=refcode);";
		return Yii::app()->kmooduldb->createCommand($sql)->queryColumn();
	}

	/**
	 * @param string the path to directory
	 * @return mixed string refcode or false if there is no value.
	 */
	public function getRefcodeByPath($directory)
	{
		$directory=$this->quote($directory);
		return Yii::app()->kmooduldb->createCommand("
			SELECT refcode
			FROM dgs.tbl_mnt
			WHERE LOWER(directory)=$directory
			LIMIT 1
		")->queryScalar();
	}

	/**
	 * @return string quoted and escaped
	 */
	protected function quote($str)
	{
		return Yii::app()->kmooduldb->quoteValue(strtolower($str));
	}

	/**
	 * @return string quoted and escaped
	 */
	protected function walkQuote(&$str)
	{
		$str=Yii::app()->kmooduldb->quoteValue(strtolower($str));
	}

	/**
	 * @return string formatted
	 */
	protected function walkFormat(&$value,$key)
	{
		if($key=='appcode')
			$value=$this->getAppText($value);
		elseif($key=='status')
			$value=$this->getStatusText($value);
	}

	/**
	 * @return array status options
	 */
	protected function getStatusOptions()
	{
		return array(
			self::STATUS_PUBLIC=>'Public',
			self::STATUS_DISCONNECTED=>'Disconnected',
			self::STATUS_INVISIBLE=>'Invisible',
			self::STATUS_FORBIDDEN=>'Forbidden',
		);
	}

	/**
	 * @param integer status code
	 * @return string status name
	 */
	protected function getStatusText($status)
	{
		$options=$this->statusOptions;
		return isset($options[$status]) ? $options[$status] : "unknown ({$status})";
	}

	/**
	 * @return array app options
	 */
	protected function getAppOptions()
	{
		return array(
			self::APP_SAAGA=>'http://www.ra.ee/dgs',
			self::APP_KAART=>'http://www.ra.ee/kaardid',
			self::APP_PARGAMENT=>'http://www.ra.ee/pargamendid',
			self::APP_PITSER=>'http://www.ra.ee/pitserid',
			self::APP_FOTIS=>'http://www.ra.ee/fotis',
		);
	}

	/**
	 * @param integer application code
	 * @return string app name
	 */
	protected function getAppText($appcode)
	{
		$options=$this->appOptions;
		return isset($options[$appcode]) ? $options[$appcode] : "unknown ({$appcode})";
	}
}