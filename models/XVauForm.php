<?php
/**
 * XDgsForm class file.
 *
 * This class contains methods that make sql queries to kmoodul database using Yii DAO
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0.0
 */
class XVauForm extends CFormModel
{
	const PLACE_IN_REPOSITORY=1;
	const PLACE_IN_WORKROOM=2;
	const PLACE_PICKUP_REPOSITORY_READINGROOM=3;
	const PLACE_IN_READINGROOM=4;
	const PLACE_PICKUP_IAL_REPOSITORY=5;
	const PLACE_PICKUP_IAL_READINGROOM=6;
	const PLACE_IN_COPYCENTER=7;
	const PLACE_PICKUP_COPYCENTER_TO_READINGROOM=8;
	const PLACE_PICKUP_COPYCENTER_TO_REPOSITORY=9;
	const PLACE_IN_DEPOSITION=10;
	const PLACE_IN_PICKUPROOM=11;
	const PLACE_PICKUPROOM_TO_REPOSITORY=12;

	/**
	 * @param string item reference code
	 * @return array item location data
	 *
	 * Example:
	 * Array
	 * (
	 *     [place_code] => KL<>US
	 *     [current_room] => Ajalooarhiivi uurimissaal
	 *     [current_room_address] => J. Liivi 4, Tartu
	 *     [current_room_unit] => Ajalooarhiiv
	 *     [original_repository] => ERA.M7
	 *     [original_repository_address] => Madara 24, Tallinn
	 *     [original_repository_unit] => Riigiarhiiv
	 * )
	 *
	 */
	public function getItemLocation($reference)
	{
		$reference=$this->quote($reference);
		$data = Yii::app()->kmooduldb->createCommand("
			SELECT
				i.place_code,
				r.name_et AS current_room,
				b.address AS current_room_address,
				u.title_et AS current_room_unit,
				r2.name_et AS original_repository,
				b2.address AS original_repository_address,
				u2.title_et AS original_repository_unit
			FROM tbl_item i
			INNER JOIN tbl_room r ON (i.room_id=r.id)
			INNER JOIN tbl_building b ON (r.building_id=b.id)
			INNER JOIN tbl_unit u ON (b.unit_id=u.id)
			INNER JOIN tbl_room r2 ON (i.repository_id=r2.id)
			INNER JOIN tbl_building b2 ON (r2.building_id=b2.id)
			INNER JOIN tbl_unit u2 ON (b2.unit_id=u2.id)
			WHERE LOWER(i.refcode)=$reference
		")->queryRow();
		if($data)
			array_walk_recursive($data, array($this, 'walkFormat'));
		return $data;
	}

	/**
	 * @param string item reference code
	 * @return boolean whether item has public access
	 */
	public function checkBlacklist($reference)
	{
		$reference=$this->quote($reference);
		$id=Yii::app()->kmooduldb->createCommand("
			SELECT id
			FROM tbl_blacklist
			WHERE refcode=$reference
			LIMIT 1
		")->queryScalar();
		return $id ? true : false;
	}

	/**
	 * @param string item reference code
	 * @param integer user id
	 * @return boolean whether user has access to item
	 */
	public function checkWhitelist($reference, $userId)
	{
		$reference=$this->quote($reference);
		$id=Yii::app()->kmooduldb->createCommand("
			SELECT id
			FROM tbl_whitelist
			WHERE refcode=$reference
			AND rightholder_user_id=$userId
			LIMIT 1
		")->queryScalar();
		return $id ? true : false;
	}

	/**
	 * @return string quoted and escaped
	 */
	protected function quote($str)
	{
		return Yii::app()->kmooduldb->quoteValue(mb_strtolower($str));
	}

	/**
	 * @return string formatted
	 */
	protected function walkFormat(&$value,$key)
	{
		if($key=='place_code')
			$value=$this->getPlaceLabel($value);
	}

	/**
	 * @return array place options
	 */
	protected function getPlaceOptions()
	{
		return array(
			self::PLACE_IN_REPOSITORY=>'Hoidlas',
			self::PLACE_IN_WORKROOM=>'Tööruumis',
			self::PLACE_PICKUP_REPOSITORY_READINGROOM=>'H<>US',
			self::PLACE_IN_READINGROOM=>'Uurimissaalis',
			self::PLACE_PICKUP_IAL_REPOSITORY=>'H<>KL',
			self::PLACE_PICKUP_IAL_READINGROOM=>'KL<>US',
			self::PLACE_IN_COPYCENTER=>'Koopiakeskuses',
			self::PLACE_PICKUP_COPYCENTER_TO_READINGROOM=>'KK>US',
			self::PLACE_PICKUP_COPYCENTER_TO_REPOSITORY=>'KK>H',
			self::PLACE_IN_DEPOSITION=>'Deponeerimisel',
			self::PLACE_IN_PICKUPROOM=>'Kogumispunktis',
			self::PLACE_PICKUPROOM_TO_REPOSITORY=>'KP>H',
		);
	}

	/**
	 * @return integer place code
	 * @return string place option name
	 */
	protected function getPlaceLabel($place_code)
	{
		$options=$this->placeOptions;
		return isset($options[$place_code]) ? $options[$place_code] : "unknown ({$place_code})";
	}
}