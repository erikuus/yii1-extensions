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
	public function getItemRoomPlace($reference)
	{
		$reference=$this->quote($reference);
		$data = Yii::app()->kmooduldb->createCommand("
			SELECT t.place_code, r.name_et
			FROM tbl_item t
			INNER JOIN tbl_room r ON (t.room_id=r.id)
			WHERE refcode=$reference
		")->queryRow();
		array_walk_recursive($data, array($this, 'walkFormat'));
		return $data;
	}


	/**
	 * @return string quoted and escaped
	 */
	protected function quote($str)
	{
		return Yii::app()->kmooduldb->quoteValue(strtolower($str));
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