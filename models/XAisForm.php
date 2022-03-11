<?php
/**
 * XAisForm class file.
 *
 * This class contains methods that make sql queries to AIS database using Yii DAO
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0.0
 */
class XAisForm extends CFormModel
{
	const CACHE_DURATION=600;

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
			SELECT u.kood, u.kirjeldusyksus, u.tyyp, u.leidandmed, u.pealkiri, ra.ky_aeg_list(u.kood,'MOOD') AS piirdaatumid
			FROM ra.kirjeldusyksus u
			INNER JOIN ra.kirjeldusyksus p ON u.kirjeldusyksus=p.kood
			WHERE p.leidandmed=".$this->quote($reference)."
			ORDER BY u.jarjekord
		";

		return Yii::app()->aisdb->createCommand($sql)->queryAll();
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
		$top=$limit ? "TOP $limit" : null;

		$sql = "
			SELECT $top kood, kirjeldusyksus, tyyp, leidandmed, pealkiri, ra.ky_aeg_list(kood,'MOOD') AS piirdaatumid
			FROM ra.kirjeldusyksus
			WHERE kirjeldusyksus=".$this->quote($code)."
			ORDER BY jarjekord
		";

		return Yii::app()->aisdb->createCommand($sql)->queryAll();
	}

	/**
	 * Find descriptive unit data and years
	 * @param mixed $reference unit reference code(s)
	 * @param boolean $checkFondsOnly whether to check units on fond type only
	 * @param boolean $checkItemsOnly whether to check units on item type only
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
			$list=implode(',',$reference);
			$condition='t.leidandmed IN ('.$list.')';
			$queryMethod='queryAll';
		}
		else
		{
			$condition='t.leidandmed='.$this->quote($reference);
			$queryMethod='queryRow';
		}

		$sql = "
			SELECT t.kood,
				   t.kirjeldusyksus,
				   t.tyyp,
				   t.leidandmed,
				   ra.ky_aeg_list(t.kood,'MOOD') AS piirdaatumid,
				   t.pealkiri,
				   t.jarjekord,
				   YEARS(MIN(y.algusaeg)) AS algusaasta,
				   YEARS(IF max(loppaeg) IS NULL OR max(loppaeg) < max(algusaeg) THEN  max(algusaeg) ELSE max(loppaeg) ENDIF) AS loppaasta
			FROM   ra.kirjeldusyksus AS t
			LEFT OUTER JOIN ra.ky_aeg AS y
			ON     t.kood=y.kirjeldusyksus
			WHERE  t.tyyp IN ('ARH', 'KOLL','AHV') AND $condition
			GROUP BY
				   t.kood,
				   t.kirjeldusyksus,
				   t.tyyp,
				   t.pealkiri,
				   t.leidandmed,
				   t.jarjekord
		";
		return Yii::app()->aisdb->createCommand($sql)->{$queryMethod}();
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
			SELECT kood, kirjeldusyksus, tyyp, leidandmed, ra.ky_aeg_list(kood,'MOOD') AS piirdaatumid, pealkiri
			FROM ra.kirjeldusyksus
			WHERE leidandmed=".$this->quote($reference)."
		";

		if($checkFondsOnly)
			$sql.=" AND tyyp IN ('ARH', 'KOLL')";
		elseif($checkItemsOnly)
			$sql.=" AND tyyp NOT IN ('ARH', 'KOLL')";

		return Yii::app()->aisdb->cache(self::CACHE_DURATION)->createCommand($sql)->queryRow();
	}

	/**
	 * Find descriptive unit data
	 * @param integer unit parent id
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
	public function findUnitByParentId($parentId)
	{
		$sql = "
			SELECT kood, kirjeldusyksus, tyyp, leidandmed, ra.ky_aeg_list(kood,'MOOD') AS piirdaatumid, pealkiri
			FROM ra.kirjeldusyksus
			WHERE kood=".$this->quote($parentId)."
		";
		return Yii::app()->aisdb->cache(self::CACHE_DURATION)->createCommand($sql)->queryRow();
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
		$arrReference=$this->getReferenceArray($reference);
		$archiveIds=$this->getArchiveIds($arrReference['a']);
		$sql = "
			SELECT h.nimetus
			FROM ra.sailik s, ra.hoidla h
			WHERE s.hoidla=h.kood
			AND fondi_nr=".$this->quote($arrReference['f'])."
			AND s.yksus in ($archiveIds)
			GROUP BY h.nimetus
		";
		return Yii::app()->aisdb->cache(self::CACHE_DURATION)->createCommand($sql)->queryColumn();
	}

	/**
	 * Find fond archive id
	 * @param string fond reference code
	 * @return array storage data of given fond (false if no result).
	 * Example:
	 * array (
	 * 	200
	 * )
	 */
	public function findFondArchiveId($reference)
	{
		$arrReference=$this->getReferenceArray($reference);
		$archiveIds=$this->getArchiveIds($arrReference['a']);
		$sql = "
			SELECT yksus
			FROM ra.sailik
			WHERE fondi_nr=".$this->quote($arrReference['f'])."
			AND yksus in ($archiveIds)
			GROUP BY yksus
		";
		return Yii::app()->aisdb->cache(self::CACHE_DURATION)->createCommand($sql)->queryColumn();
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
		$arrReference=$this->getReferenceArray($reference);
		$archiveIds=$this->getArchiveIds($arrReference['a']);
		$sql = "
			SELECT h.nimetus AS hoidla, r.tahis AS riiul, k.tahis AS kapp, l.tahis AS laudi, s.yksus AS yksus
			FROM ra.sailik s
			LEFT OUTER JOIN ra.hoidla h ON s.hoidla=h.kood
			LEFT OUTER JOIN ra.riiul r ON s.riiul=r.kood
			LEFT OUTER JOIN ra.kapp k ON s.kapp=k.kood
			LEFT OUTER JOIN ra.laudi l ON s.laudi=l.kood
			WHERE fondi_nr=".$this->quote($arrReference['f'])."
			AND nimistu_nr=".$this->quote($arrReference['n'])."
			AND sailiku_nr=".$this->quote($arrReference['s'])."
			AND s.yksus in ($archiveIds)
		";
		return Yii::app()->aisdb->cache(self::CACHE_DURATION)->createCommand($sql)->queryRow();
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
				ky.leidandmed,
				ky.pealkiri,
				ra.ky_aeg_list(ky.kood,'MOOD') AS piirdaatumid,
				h.nimetus AS hoidla,
				r.tahis AS riiul,
				k.tahis AS kapp,
				l.tahis AS laudi,
				s.yksus AS yksus
			FROM ra.kirjeldusyksus ky
			KEY INNER JOIN ra.ky_sailik
			KEY INNER JOIN ra.sailik s
			LEFT OUTER JOIN ra.hoidla h ON s.hoidla=h.kood
			LEFT OUTER JOIN ra.riiul r ON s.riiul=r.kood
			LEFT OUTER JOIN ra.kapp k ON s.kapp=k.kood
			LEFT OUTER JOIN ra.laudi l ON s.laudi=l.kood
			WHERE ky.staatus='AKT'
			AND ky.tyyp IN ('KOLL','AHV')
			AND ky.leidandmed=".$this->quote($reference)."
		";
		return Yii::app()->aisdb->cache(self::CACHE_DURATION)->createCommand($sql)->queryRow();
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
		$arrReference=$this->getReferenceArray($fromReference);
		$archiveIds=$this->getArchiveIds($arrReference['a']);
		$from=$this->getSortableReference($fromReference);
		$to=$this->getSortableReference($toReference);
		if($from && $to)
		{
			$sql = "
				SELECT TOP $limit
					ky.leidandmed,
					ky.pealkiri,
					ra.ky_aeg_list(ky.kood,'MOOD') AS piirdaatumid,
					h.nimetus AS hoidla,
					r.tahis AS riiul,
					k.tahis AS kapp,
					l.tahis AS laudi,
					s.yksus AS yksus
				FROM ra.kirjeldusyksus ky
				KEY INNER JOIN ra.ky_sailik
				KEY INNER JOIN ra.sailik s
				LEFT OUTER JOIN ra.hoidla h ON s.hoidla=h.kood
				LEFT OUTER JOIN ra.riiul r ON s.riiul=r.kood
				LEFT OUTER JOIN ra.kapp k ON s.kapp=k.kood
				LEFT OUTER JOIN ra.laudi l ON s.laudi=l.kood
				WHERE ky.staatus='AKT'
				AND ky.tyyp IN ('KOLL','AHV')
				AND s.leidandmed BETWEEN '$from' AND '$to'
				AND s.yksus in ($archiveIds)
			";
			return Yii::app()->aisdb->cache(self::CACHE_DURATION)->createCommand($sql)->queryAll();
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
		$arrReference=$this->getReferenceArray($fromReference);
		$archiveIds=$this->getArchiveIds($arrReference['a']);
		$from=$this->getSortableReference($fromReference);
		$to=$this->getSortableReference($toReference);
		if($from && $to)
		{
			$sql = "
				SELECT LIST(ky.leidandmed)
				FROM ra.kirjeldusyksus ky
				KEY INNER JOIN ra.ky_sailik
				KEY INNER JOIN ra.sailik s
				WHERE ky.staatus='AKT'
				AND ky.tyyp IN ('KOLL','AHV')
				AND s.leidandmed BETWEEN '$from' AND '$to'
				AND s.yksus in ($archiveIds)
			";
			return Yii::app()->aisdb->createCommand($sql)->queryScalar();
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
				SELECT LIST(leidandmed)
				FROM ra.kirjeldusyksus
				WHERE leidandmed LIKE $reference;
			";
			return Yii::app()->aisdb->createCommand($sql)->queryScalar();
		}
		else
			return null;
	}

	/**
	 * Find list of sub item references by parent code
	 * @param string $fromReference item reference code starting range
	 * @param string $toReference item reference code limiting range
	 * @return string comma separated list of item references
	 * Example: EAA.1.1.1,EAA.1.1.10,EAA.1.1.100,EAA.1.1.101,EAA.1.1.102,EAA.1.1.103
	 */
	public function findSubItemReferenceList($code)
	{
		if($code)
		{
			$code=$this->quote($code);

			$sql = "
				SELECT LIST(leidandmed)
				FROM ra.kirjeldusyksus
				WHERE kirjeldusyksus=$code;
				AND tyyp='AHV'
			";
			return Yii::app()->aisdb->createCommand($sql)->queryScalar();
		}
		else
			return null;
	}

	/**
	 * @param item reference code (ex. EAA.1.2.1)
	 * @return string sortable reference (ex.  0001      0000   0000000002  000000000001)
	 */
	public function getSortableReference($reference)
	{
		$arrReference=$this->getReferenceArray($reference);
		$archiveIds=$this->getArchiveIds($arrReference['a']);
		$sql = "
			SELECT leidandmed
			FROM ra.sailik
			WHERE fondi_nr=".$this->quote($arrReference['f'])."
			AND nimistu_nr=".$this->quote($arrReference['n'])."
			AND sailiku_nr=".$this->quote($arrReference['s'])."
			AND yksus in ($archiveIds)
		";
		return Yii::app()->aisdb->cache(self::CACHE_DURATION)->createCommand($sql)->queryScalar();
	}

	/**
	 * @param item reference code (ex. EAA.1.2.1)
	 * @return string sortable reference (ex.  0001      0000   0000000002  000000000001)
	 */
	public function getSortableReferenceByFunction($reference)
	{
		$arrReference=$this->getReferenceArray($reference);
		$sql = "
			SELECT * FROM ra.z_leidandmed_to_string
			(
				".$this->quote($arrReference['f']).",
				".$this->quote($arrReference['n']).",
				".$this->quote($arrReference['s'])."
			)
		";
		return Yii::app()->aisdb->cache(self::CACHE_DURATION)->createCommand($sql)->queryScalar();
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
			SELECT nimetus, hoidla_nr, asukoht, korrus, yksus
			FROM ra.hoidla
			WHERE nimetus=".$this->quote($reference)."
		";

		return Yii::app()->aisdb->cache(self::CACHE_DURATION)->createCommand($sql)->queryRow();
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
	 * @param archive acronym
	 * @return list of archives numeric codes
	 */
	public function getArchiveIds($archive)
	{
		$options=$this->archiveIds();
		$archive=strtoupper($archive);
		return isset($options[$archive]) ? $options[$archive] : 0;
	}

	/**
	 * @return array of archives numeric codes
	 */
	protected function archiveIds()
	{
		return array(
			'EAA'=>'200',
			'ERA'=>'110,121,215,217,219',
			'ERAF'=>'111,216,218',
			'EFA'=>'122,224',
			'HAMA'=>'112',
			'LAMA'=>'115',
			'LVMA'=>'117,220,222',
			'SAMA'=>'119',
			'VAMA'=>'213',
			'TLA'=>'124',
			'MKA'=>'123',
			'EAM'=>'125',
			'EELKKA'=>'128',
			'TTI'=>'701',
		);
	}

	/**
	 * @return string escaped for sybase db
	 */
	protected function quote($str)
	{
		return Yii::app()->aisdb->quoteValue($str);
	}

	/**
	 * @return string quoted and escaped
	 */
	protected function walkQuote(&$str)
	{
		$str=Yii::app()->aisdb->quoteValue($str);
	}
}