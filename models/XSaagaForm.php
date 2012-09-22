<?php
/**
 * XSaagaForm class file.
 *
 * This class contains methods that make sql queries to SAAGABAAS database using Yii DAO
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0.0
 */
class XSaagaForm extends CFormModel
{
	const CACHE_DURATION=600;

	/**
	 * Find descriptive unit of  Saaga
	 * @param string unit reference code
	 * @param boolean $checkFondsOnly whether to check units on fond type only
	 * @param boolean $checkItemsOnly whether to check units on item type only
	 * @return string unit reference code
	 */
	public function findUnit($reference, $checkFondsOnly=false, $checkItemsOnly=false)
	{
		$sql1 = "SELECT leidandmed FROM  dgs_ais_unit WHERE leidandmed=".$this->quote($reference);
		$sql2 = "SELECT leidandmed FROM  dgs_ais_hidden WHERE leidandmed=".$this->quote($reference);

		if($checkFondsOnly)
		{
			$sql1.=" AND tyyp IN ('ARH', 'KOLL')";
			$sql2.=" AND tyyp IN ('ARH', 'KOLL')";
		}
		elseif($checkItemsOnly)
		{
			$sql1.=" AND tyyp NOT IN ('ARH', 'KOLL')";
			$sql2.=" AND tyyp NOT IN ('ARH', 'KOLL')";
		}

		$sql = "$sql1 UNION $sql2";

		return Yii::app()->saagadb->cache(self::CACHE_DURATION)->createCommand($sql)->queryScalar();
	}

	/**
	 * Find descriptive units of  Saaga
	 * @param array units reference codes
	 * @param boolean $checkFondsOnly whether to check units on fond type only
	 * @param boolean $checkItemsOnly whether to check units on item type only
	 * @return array units reference codes
	 * Example:
	 * Array
	 * (
	 *   [0] => ERAF.24.1.281
	 *   [1] => ERA.2280.1.1
	 *   [2] => EAA.1427.1.24
	 *   [3] => EAA.2072.3.320
	 *   [4] => ERA.1608.2.2260
	 * )
	 */
	public function findAllUnits($arrReference, $checkFondsOnly=false, $checkItemsOnly=false)
	{
		array_walk($arrReference, array($this, 'walkQuote'));

		$list=implode(',',$arrReference);

		$sql1 = "SELECT leidandmed FROM  dgs_ais_unit WHERE leidandmed IN ($list)";
		$sql2 = "SELECT leidandmed FROM  dgs_ais_hidden WHERE leidandmed IN ($list)";

		if($checkFondsOnly)
		{
			$sql1.=" AND tyyp IN ('ARH', 'KOLL')";
			$sql2.=" AND tyyp IN ('ARH', 'KOLL')";
		}
		elseif($checkItemsOnly)
		{
			$sql1.=" AND tyyp NOT IN ('ARH', 'KOLL')";
			$sql2.=" AND tyyp NOT IN ('ARH', 'KOLL')";
		}

		$sql = "$sql1 UNION $sql2";
		return Yii::app()->saagadb->cache(self::CACHE_DURATION)->createCommand($sql)->queryColumn();
	}

	/**
	 * Get unit map
	 * @param string unit reference code
	 * @param boolean $checkFondsOnly whether to check units on fond type only
	 * @param boolean $checkItemsOnly whether to check units on item type only
	 * @return array map of given unit
	 * Example:
	 * array (
	 *   [public] => array()
	 *   [disconnected] => array(
	 *     [0] => ERAF.24.1.281
	 *   )
	 *   [invisible] => array()
	 *   [forbidden] => array()
	 * )
	 */
	public function getUnitMap($reference, $checkFondsOnly=false, $checkItemsOnly=false)
	{
		$saagaReference=$this->findUnit($reference, $checkFondsOnly, $checkItemsOnly);

		if($saagaReference!==false)
		{
			$filtered=$this->applyMap(array($saagaReference));
			return $filtered;
		}
		else
			return false;
	}

	/**
	 * Get mapped units
	 * @param array unit reference codes
	 * @param boolean $checkFondsOnly whether to check units on fond type only
	 * @param boolean $checkItemsOnly whether to check units on item type only
	 * @return array map of units
	 * Example:
	 * array (
	 *   [public] => array(
	 *     [0] => EAA.1427.1.24
	 *     [1] => EAA.2072.3.320
	 *   )
	 *   [disconnected] => array(
	 *     [0] => ERAF.24.1.281
	 *   )
	 *   [invisible] => array(
	 *     [0] => ERA.2280.1.1
	 *   )
	 *   [forbidden] => array(
	 *     [0] => ERA.1608.2.2260
	 *   )
	 * )
	 */
	public function getMappedUnits($references, $checkFondsOnly=false, $checkItemsOnly=false)
	{
		$saagaReferences=$this->findAllUnits($references, $checkFondsOnly, $checkItemsOnly);

		if($saagaReferences!==array())
		{
			$filtered=$this->applyMap($saagaReferences);
			return $filtered;
		}
		else
			return array();
	}

	/**
	 * @param array reference codes
	 * @return array of references divided into groups
	 */
	protected function applyMap($references)
	{
		$filtered=array(
			'public'=>array(),
			'disconnected'=>array(),
			'invisible'=>array(),
			'forbidden'=>array(),
		);

		foreach ($references as $reference)
		{
			$fond=$this->getReferenceFond($reference);

			$disconnectedFonds=$this->getDisconnectedFonds();
			$invisibleFonds=$this->getInvisibleFonds();
			$forbiddenItems=$this->getForbiddenItems();

			if(in_array($fond, $disconnectedFonds))
				$filtered['disconnected'][]=$reference;
			elseif(in_array($fond, $invisibleFonds))
				$filtered['invisible'][]=$reference;
			elseif(in_array($reference, $forbiddenItems))
				$filtered['forbidden'][]=$reference;
			else
				$filtered['public'][]=$reference;
		}
		return $filtered;
	}

	/**
	 * @param array unit reference code (ex. EAA.1.1.1)
	 * @return string fond code from reference (ex. EAA.1)
	 */
	protected function getReferenceFond($reference)
	{
		$arr=explode('.',$reference,3);
		return isset($arr[0]) && isset($arr[1]) ? $arr[0].'.'.$arr[1] : null;
	}

	/**
	 * @return array of references of items that have access restriction
	 * Example:
	 * array(
	 *   [0]=>ERA.1608.2.2260
	 * )
	 */
	protected function getForbiddenItems()
	{
		$sql = "
			SELECT leidandmed FROM  dgs_ais_unit WHERE kood IN (SELECT ais_id FROM dgs_ais_forbidden)
			UNION
			SELECT leidandmed FROM  dgs_ais_hidden WHERE kood IN (SELECT ais_id FROM dgs_ais_forbidden)
		";
		return Yii::app()->saagadb->cache(self::CACHE_DURATION)->createCommand($sql)->queryColumn();
	}

	/**
	 * @return array of references of fonds that are disconnected
	 * Example:
	 * array(
	 *   [0]=>ERAF.24
	 * )
	 */
	protected function getDisconnectedFonds()
	{
		$sql = "
			SELECT leidandmed
			FROM   dgs_ais_unit
			WHERE  tyyp IN ('ARH', 'KOLL')
			AND kood NOT IN (SELECT ais_id FROM dgs_link_ais2tree)
		";
		return Yii::app()->saagadb->cache(self::CACHE_DURATION)->createCommand($sql)->queryColumn();
	}

	/**
	 * @return array of references of fonds that are invisible
	 * Example:
	 * array(
	 *   [0]=>ERA.2280
	 * )
	 */
	protected function getInvisibleFonds()
	{
		$sql = "
			SELECT leidandmed
			FROM   dgs_ais_unit
			WHERE  tyyp IN ('ARH', 'KOLL')
			AND kood IN (
				SELECT link.ais_id
				FROM dgs_link_ais2tree AS link
				INNER JOIN dgs_tree AS tree ON tree.id=link.tree_id
				WHERE tree.status=1
			)
		";
		return Yii::app()->saagadb->cache(self::CACHE_DURATION)->createCommand($sql)->queryColumn();
	}

	/**
	 * @return string quoted and escaped
	 */
	protected function quote($str)
	{
		return Yii::app()->saagadb->quoteValue($str);
	}

	/**
	 * @return string quoted and escaped
	 */
	protected function walkQuote(&$str)
	{
		$str=Yii::app()->saagadb->quoteValue($str);
	}
}