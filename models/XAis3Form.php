<?php
/**
 * XAis3Form class file.
 *
 * This class contains methods that make sql queries to AIS database using Yii DAO
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0.0
 */
class XAis3Form extends CFormModel
{
	const CACHE_DURATION=0;

	/**
	 * Find descriptive units data by parent reference
	 * @param string $reference the unit reference code
	 * @return array of subunit data
	 * Example:
	 * array (
	 *   [0] => array (
	 *     [kood] => 200250493892
	 *     [kirjeldusyksus] => 200100001006
	 *     [tyyp] => SAR
	 *     [leidandmed] => null
	 *     [pealkiri] => Statistilised andmed kiriku ja rüütlimõisate müümata maade külvi üle
	 *     [piirdaatumid] => null
	 *   )
	 *   [1] => array (
	 *     [kood] => 200250493968
	 *     [kirjeldusyksus] => 200100001006
	 *     [tyyp] => SAR
	 *     [leidandmed] => null
	 *     [pealkiri] => Statistilised andmed koolide ja õpilaste kohta
	 *     [piirdaatumid] => null
	 *   )
	 *   [2] => array(
	 *     [kood] => 200250494091
	 *     [kirjeldusyksus] => 200100001006
	 *     [tyyp] => SAR
	 *     [leidandmed] => null
	 *     [pealkiri] => Ettepanekud Maapäevale
	 *     [piirdaatumid] => null
	 *   )
	 * )
	 */
	public function findSubUnitsByReference($reference)
	{
		$sql = "
			SELECT
				du.id::varchar           as kood,
				du.parent_id::varchar    as kirjeldusyksus,
				du.out_tyyp     as tyyp,
				du.fns          as leidandmed,
				du.name         as pealkiri,
				du.period       as piirdaatumid
			FROM api_description_unit_mv du
				INNER JOIN api_description_unit_mv du2 
					ON du.parent_id = du2.id
			WHERE du2.fns_search = lower({$this->quote($reference)})
			ORDER BY du.sequence;
		";

		return Yii::app()->ais3db->createCommand($sql)->queryAll();
	}

	/**
	 * Find descriptive units data by parent code
	 * @param integer $code the unit reference code
	 * @return array of subunit data
	 * Example:
	 * array (
	 *   [0] => array (
	 *     [kood] => 200250493904
	 *     [kirjeldusyksus] => 200250493892
	 *     [tyyp] => AHV
	 *     [leidandmed] => EAA.1427.1.48
	 *     [pealkiri] => Statistilised andmed kiriku- ja rüütlimõisate müümata maade külvi üle. Pärnu maakond
	 *     [piirdaatumid] => 1893
	 *   )
	 *   [1] => array (
	 *     [kood] => 200250493910
	 *     [kirjeldusyksus] => 200250493892
	 *     [tyyp] => AHV
	 *     [leidandmed] => EAA.1427.1.49
	 *     [pealkiri] => Statistilised andmed kiriku- ja rüütlimõisate müümata maade külvi üle. Tartu maakond
	 *     [piirdaatumid] => 1893
	 *   )
	 *   [2] => array(
	 *     [kood] => 200250493915
	 *     [kirjeldusyksus] => 200250493892
	 *     [tyyp] => AHV
	 *     [leidandmed] => EAA.1427.1.50
	 *     [pealkiri] => Statistilised andmed kiriku- ja rüütlimõisate müümata maade külvi üle. Tartu maakond
	 *     [piirdaatumid] => 1893
	 *   )
	 * )
	 */
	public function findSubUnitsByCode($code, $limit=null)
	{
		$limit_condition = $limit ? "LIMIT $limit" : null;

		$sql = "
			SELECT
				du.id::varchar          as kood,
				du.parent_id::varchar   as kirjeldusyksus,
				du.out_tyyp             as tyyp,
				du.fns                  as leidandmed,
				du.name                 as pealkiri,
				du.period               as piirdaatumid,
				du.sequence::varchar    as jarjekord,
    			du.out_algusaasta       as algusaasta,
    			coalesce(du.out_loppaasta, du.out_algusaasta) as loppaasta
			FROM api_description_unit_mv du
			WHERE du.parent_id = {$this->quote($code)}
			ORDER BY du.sequence
			$limit_condition;
		";

		return Yii::app()->ais3db->createCommand($sql)->queryAll();
	}

	/**
	 * Find descriptive unit data and years
	 * @param mixed $reference unit reference code(s)
	 * @return array (or multiple array) of unit data
	 * Example:
	 * array(
	 *  [kood]=>200100000839
	 *  [kirjeldusyksus]=>NULL
	 *  [tyyp]=>ARH
	 *  [leidandmed]=>EAA.1248
	 *  [piirdaatumid]=>1516-1935
	 *  [pealkiri]=>EELK Ambla kogudus
	 *  [jarjekord]=>1
	 *  [algusaasta]=>1516
	 *  [loppaasta]=>1935
	 * )
	 */
	public function findUnitWithYears($reference)
	{
		if(is_array($reference))
		{
			array_walk($reference, array($this, 'walkQuote'));
			$list = implode(',',$reference);
			$list = mb_strtolower($list);
			$condition = "AND du.fns_search IN ($list)";
			$queryMethod='queryAll';
		}
		else
		{
			$condition = "AND du.fns_search = lower({$this->quote($reference)})";
			$queryMethod='queryRow';
		}

		$sql = "
			SELECT
				du.id::varchar          as kood,
				du.parent_id::varchar   as kirjeldusyksus,
				du.out_tyyp             as tyyp,
				du.fns                  as leidandmed,
				du.period               as piirdaatumid,
				du.name                 as pealkiri,
				du.sequence::varchar    as jarjekord,
				du.out_algusaasta       as algusaasta,
				coalesce(du.out_loppaasta, du.out_algusaasta) as loppaasta
			FROM api_description_unit_mv du
			WHERE du.unit_level IN (0, 6)
			$condition
		";

		return Yii::app()->ais3db->createCommand($sql)->{$queryMethod}();
	}

	/**
	 * Find descriptive unit data
	 * @param string $reference unit reference code
	 * @param boolean $checkFondsOnly whether to check units on fond type only
	 * @param boolean $checkItemsOnly whether to check units on item type only
	 * @return array of unit data
	 * Example:
	 * array(
	 *  [kood]=>200100000839
	 *  [kirjeldusyksus]=>NULL
	 *  [tyyp]=>ARH
	 *  [leidandmed]=>EAA.1248
	 *  [piirdaatumid]=>1516-1935
	 *  [pealkiri]=>EELK Ambla kogudus
	 * )
	 */
	public function findUnitByReference($reference, $checkFondsOnly=false, $checkItemsOnly=false)
	{
		$sql = "
			SELECT
				du.id::varchar          as kood,
				du.parent_id::varchar   as kirjeldusyksus,
				du.out_tyyp             as tyyp,
				du.fns                  as leidandmed,
				du.period               as piirdaatumid,
				du.name                 as pealkiri
			FROM api_description_unit_mv du
			WHERE du.fns_search = lower({$this->quote($reference)})
		";

		if($checkFondsOnly)
			$sql .= " AND du.unit_level = 0";
		elseif($checkItemsOnly)
			$sql .= " AND du.unit_level != 0";

		return Yii::app()->ais3db->cache(self::CACHE_DURATION)->createCommand($sql)->queryRow();
	}

	/**
	 * Find descriptive unit data
	 * @param integer unit id
	 * @return array of unit data
	 * Example:
	 * array (
	 *  [kood]=>200100000839
	 *  [kirjeldusyksus]=>NULL
	 *  [tyyp]=>ARH
	 *  [leidandmed]=>EAA.1248
	 *  [piirdaatumid]=>1516-1935
	 *  [pealkiri]=>EELK Ambla kogudus
	 * )
	 */
	public function findUnitById($id)
	{
		$sql = "
			SELECT
				du.id::varchar          as kood,
				du.parent_id::varchar   as kirjeldusyksus,
				du.out_tyyp             as tyyp,
				du.fns                  as leidandmed,
				du.period               as piirdaatumid,
				du.name                 as pealkiri
			FROM api_description_unit_mv du
			WHERE du.id = {$this->quote($id)}
			ORDER BY du.sequence
		";

		return Yii::app()->ais3db->cache(self::CACHE_DURATION)->createCommand($sql)->queryRow();
	}

	/**
	 * Find fond storage info
	 * @param string fond reference code
	 * @return array storage data of given fond (false if no result).
	 * Example:
	 * array (
	 * 	"EAA.Liivi:2-11"
	 * 	"EAA.Vahi:08"
	 * 	"EAA.Vahi:11"
	 * )
	 */
	public function findFondStorage($reference)
	{
		$sql = "
			SELECT DISTINCT dus.room_name
			FROM api_description_unit_mv du
				RIGHT JOIN description_unit_storage_mv dus 
					ON du.id = dus.description_unit_id
			WHERE du.archival_fond_token_search = lower({$this->quote($reference)});
		";

		return Yii::app()->ais3db->cache(self::CACHE_DURATION)->createCommand($sql)->queryColumn();
	}

	/**
	 * Find item storage info
	 * @param string item reference code
	 * @return array storage data of given item (false if no result).
	 * Example:
	 * array (
	 * 	[hoidla]=>"EAA.Liivi:4-02"
	 * 	[riiul]=>"Ia"
	 * 	[kapp]=>"I"
	 * 	[laudi]=>"4"
	 * )
	 */
	public function findStorage($reference)
	{
		$sql = "
			SELECT
				dus.room_name       as hoidla,
				dus.section_name 	as riiul,
				dus.case_name 		as kapp,
				dus.shelf_name 	    as laudi,
				coalesce(dus.building_id, 24)	as yksus -- if null, return 24 (Noora)
			FROM api_description_unit_mv du
				RIGHT JOIN description_unit_storage_mv dus on du.id = dus.description_unit_id
			WHERE du.fns_search = lower({$this->quote($reference)});
		";

		return Yii::app()->ais3db->cache(self::CACHE_DURATION)->createCommand($sql)->queryRow();
	}

	/**
	 * Find item data alongside with storage info
	 * @param string item reference code
	 * @return array item and storage data
	 * Example:
	 * array (
	 *  [leidandmed] => EAA.308.2.118
	 *  [pealkiri] => Vorbuse mõisa maade kaart
	 *  [piirdaatumid] => 1700
	 *  [hoidla] => EAA.Vahi:08
	 *  [riiul] => XVII
	 *  [kapp] => 1
	 *  [laudi] => 3
	 * )
	 */
	public function findItemWithStorage($reference)
	{
		$sql = "
			SELECT
				du.fns              as leidandmed,
				du.name             as pealkiri,
				du.period           as piirdaatumid,
				dus.room_name       as hoidla,
				dus.section_name 	as riiul,
				dus.case_name 		as kapp,
				dus.shelf_name 	    as laudi,
				coalesce(dus.building_id, 24)	as yksus -- if null, return 24 (Noora)
			FROM api_description_unit_mv du
				LEFT JOIN description_unit_storage_mv dus on du.id = dus.description_unit_id
			WHERE du.fns_search = lower({$this->quote($reference)});
		";

		return Yii::app()->ais3db->cache(self::CACHE_DURATION)->createCommand($sql)->queryRow();
	}

	/**
	 * Find range of item data alongside with storage info
	 * @param string $fromReference item reference code starting range
	 * @param string $toReference item reference code limiting range
	 * @param integer $limit range limit
	 * @return array multiple item and storage data
	 * Example:
	 * array (
	 *   [0] => array (
	 *     [leidandmed] => EAA.1.1.1
	 *     [pealkiri] => Karl IX poolt kinnitatud kaupade hinnakiri Rootsi riigi sadamais
	 *     [piirdaatumid] => 1604-1609
	 *     [hoidla] => EAA.Liivi:2-11
	 *     [riiul] => LXXXVI
	 *     [kapp] => I
	 *     [laudi] => 7
	 *   )
	 *   [1] => array (
	 *     [leidandmed] => EAA.1.1.2
	 *     [pealkiri] => Kuningate kirjade ärakirjad linnade ...
	 *     [piirdaatumid] => 16.09.1613-04.09.1707
	 *     [hoidla] => EAA.Liivi:2-11
	 *     [riiul] => LXXXVI
	 *     [kapp] => I
	 *     [laudi] => 7
	 *   )
	 *   [2] => array(
	 *     [leidandmed] => EAA.1.1.3
	 *     [pealkiri] => Kuningliku Kammerkolleegiumi, kindralkuberneri ja teiste määrused ...
	 *     [piirdaatumid] => 14.06.1620-18.06.1707
	 *     [hoidla] => EAA.Liivi:2-11
	 *     [riiul] => LXXXVI
	 *     [kapp] => I
	 *     [laudi] => 7
	 *   )
	 * )
	 */
	public function findItemRangeWithStorage($fromReference, $toReference, $limit=100)
	{
		if ($fromReference && $toReference)
		{
			$sql = "
				SELECT
					du.fns              as leidandmed,
					du.name             as pealkiri,
					du.period           as piirdaatumid,
					dus.room_name       as hoidla,
					dus.section_name 	as riiul,
					dus.case_name 		as kapp,
					dus.shelf_name 	    as laudi,
				coalesce(dus.building_id, 24)	as yksus -- if null, return 24 (Noora)
				FROM api_description_unit_mv du
					LEFT JOIN description_unit_storage_mv dus ON du.id = dus.description_unit_id
				WHERE du.fns_search 
					BETWEEN lower({$this->quote($fromReference)}) AND lower({$this->quote($toReference)})
				ORDER BY du.fns_search
				LIMIT $limit
			";
			return Yii::app()->ais3db->cache(self::CACHE_DURATION)->createCommand($sql)->queryAll();
		}
		else
			return array();
	}

	/**
	 * Find list of item references by range
	 * @param string $fromReference item reference code starting range
	 * @param string $toReference item reference code limiting range
	 * @return string comma separated list of item references
	 * Example: EAA.1.1.1,EAA.1.1.10,EAA.1.1.100,EAA.1.1.101,EAA.1.1.102,EAA.1.1.103
	 */
	public function findItemReferenceRangeList($fromReference, $toReference)
	{
		if ($fromReference && $toReference)
		{
			$sql = "
				SELECT string_agg(a.fns, ',') as leidandmed
				FROM (
					SELECT du.fns FROM api_description_unit_mv du
						WHERE du.fns_search 
							BETWEEN lower({$this->quote($fromReference)}) AND lower({$this->quote($toReference)})
					ORDER BY du.fns_search
				) a
			";

			return Yii::app()->ais3db->createCommand($sql)->queryScalar();
		}
		else
			return null;
	}

	/**
	 * Find list of item references by reference pattern
	 * @param string $reference item reference code
	 * @return string comma separated list of item references
	 * Example: EAA.1.1.1,EAA.1.1.10,EAA.1.1.100,EAA.1.1.101,EAA.1.1.102,EAA.1.1.103
	 */
	public function findItemReferenceList($reference)
	{
		if($reference)
		{
			$reference=$this->quote($reference);
			$reference=mb_substr($reference, 0, -1).".%'";

			$sql = "
				SELECT string_agg(a.fns, ',') as leidandmed
				FROM (
					 SELECT du.fns FROM api_description_unit_mv du
					 WHERE du.fns_search LIKE lower({$reference})
					 ORDER BY du.fns_search
				 ) a;
			";

			return Yii::app()->ais3db->createCommand($sql)->queryScalar();
		}
		else
			return null;
	}

	/**
	 * Find list of sub item references by parent code
	 * @return string comma separated list of item references
	 * Example: EAA.1.1.1,EAA.1.1.10,EAA.1.1.100,EAA.1.1.101,EAA.1.1.102,EAA.1.1.103
	 */
	public function findSubItemReferenceList($code)
	{
		if($code)
		{
			$code=$this->quote($code);

			$sql = "
				SELECT string_agg(a.fns, ',') as leidandmed
				FROM (
					 SELECT du.fns FROM api_description_unit_mv du
						WHERE du.parent_id = {$code}
					 ORDER BY du.fns_search
				 ) a;
			";

			return Yii::app()->ais3db->createCommand($sql)->queryScalar();
		}
		else
			return null;
	}

	/**
	 * Find repository data
	 * @param string $reference repository reference code
	 * @return array of repository data
	 * Example:
	 * array(
	 *  [nimetus]=>
	 *  [hoidla_nr]=>
	 *  [asukoht]=>
	 *  [korrus]=>
	 *  [yksus]=>
	 * )
	 */
	public function findRepository($reference)
	{
		$sql = "
			SELECT
				s.room_name         as nimetus,
				s.repository_code   as hoidla_nr,
				(SELECT address FROM storage_mv WHERE id = s.parent_id) as asukoht,
				s.room_location     as korrus,
				s.parent_id			as yksus -- storage_level = 1 = building
			FROM storage_mv s
			WHERE s.storage_level = 2
			  AND s.room_name ILIKE {$this->quote($reference)}
		";

		return Yii::app()->ais3db->cache(self::CACHE_DURATION)->createCommand($sql)->queryRow();
	}

	/**
	 * @param volume reference code (ex. EAA.1.2.1)
	 * @return array of reference (ex. array(a=>EAA,f=>1,n=>2,s=>1))
	 */
	protected function getReferenceArray($reference)
	{
		$arrReference=array();
		$arr=explode('.',$reference,4);
		$arrReference['a']=isset($arr[0]) ? $arr[0] : null;
		$arrReference['f']=isset($arr[1]) ? $arr[1] : null;
		$arrReference['n']=isset($arr[2]) ? $arr[2] : null;
		$arrReference['s']=isset($arr[3]) ? $arr[3] : null;
		return $arrReference;
	}

	/**
	 * @return string escaped for sybase db
	 */
	protected function quote($str)
	{
		return Yii::app()->ais3db->quoteValue($str);
	}

	/**
	 * @return string quoted and escaped
	 */
	protected function walkQuote(&$str)
	{
		$str=Yii::app()->ais3db->quoteValue($str);
	}
}