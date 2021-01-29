<?php
/**
 * XErplyHelper class
 *
 * This class adds helper methods to get integration codes (TP) for ERPLY customer.
 *
 * First import it:
 * ```
 * 'import'=>array(
 *   'ext.components.erply.XErplyHelper',
 * ),
 * ```
 *
 * Then use it as follows:
 * ```
 * $code=XErplyHelper::getPersonIntegrationCode('EE');
 * ```
 *
 * IMPORTANT! XErplyHelper uses static data stored in array. 
 *
 * @link extensions/components/erply/vendor/tps.data.php
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XErplyHelper
{
	const FOREIGN_TYPE_GOVERNMENT_AGENCY=1;
	const FOREIGN_TYPE_LOCAL_AGENCY=2;
	const FOREIGN_TYPE_INTERNATIONAL_ORGANISATION=3;
	const FOREIGN_TYPE_FINANCIAL_INSTITUTION=4;
	const FOREIGN_TYPE_OTHER=5;

	/**
	 * Ge data from array
	 * @return array data
	 */
	public static function getData()
	{
		return include dirname(__FILE__).'/vendor/tps.data.php';
	}

	/**
	 * Get type options for foreign companies
	 * @return array options for dropdown
	 */
	public static function getForeignTypeOptions()
	{
		return array(
			self::FOREIGN_TYPE_GOVERNMENT_AGENCY=>Yii::t('ErplyHelper.erply', 'Government Agency'),
			self::FOREIGN_TYPE_LOCAL_AGENCY=>Yii::t('ErplyHelper.erply', 'Local Government Agency'),
			self::FOREIGN_TYPE_INTERNATIONAL_ORGANISATION=>Yii::t('ErplyHelper.erply', 'International Organisation'),
			self::FOREIGN_TYPE_FINANCIAL_INSTITUTION=>Yii::t('ErplyHelper.erply', 'Credit or Financial Institution'),
			self::FOREIGN_TYPE_OTHER=>Yii::t('ErplyHelper.erply', 'Other'),
		);
	}

	/**
	 * Get type name for foreign company type code
	 * @param integer $typeCode the foreign business type code
	 * @return string option name
	 */
	public static function getForeignTypeText($typeCode)
	{
		$options=self::getForeignTypeOptions();
		return isset($options[$typeCode]) ? $options[$typeCode] : null;
	}

	/**
	 * Get special dropdown options for manager
	 * @return array options for dropdown
	 */
	public static function getManagerOptions()
	{
		$data = static::getData();
		return $data['dropdownOptions'];
	}

	/**
	 * Get private person integration code
	 * @param string $countryCode the two char country code (ex. 'EE')
	 * @return string integration code
	 */
	public static function getPersonIntegrationCode($countryCode)
	{
		return self::getIntegrationCode('privatePersonByCountry', $countryCode, 'Other');
	}

	/**
	 * Get company or institution integration code
	 * @param string $name the agency, institution or company name
	 * @param string $countryCode the two char country code (ex. 'EE')
	 * @param integer $regCode the estonian business registration code
	 * @param integer $typeCode the foreign business type code
	 * @return string integration code
	 */
	public static function getCompanyIntegrationCode($name, $countryCode, $regCode, $typeCode)
	{
		$code=null;

		// For estonian companies registration code is given
		// For foregin companies type code is given
		if($regCode && $countryCode=='EE')
		{
			// first try to find integration code by registration code
			$code=self::getIntegrationCode('estCompanyByRegCode', $regCode);

			// if not returned by registration code, try to find by bank name
			if(!$code)
				$code=self::getIntegrationCode('estBankByName', $name);

			// if not returned by registration code or name, try to find by business entity
			if(!$code)
			{
				$entity=self::getBusinessEntity($name);
				if($entity)
					$code=self::getIntegrationCode('estCompanyByEntity', $entity);
			}
		}
		elseif($typeCode && $countryCode!='EE')
		{
			$method=self::getForeignTypeMethod($typeCode);
			$code=self::getIntegrationCode($method, $countryCode, 'Other');
		}

		// If normal procedure could not determine
		// integration code, use these defaults
		if(!$code)
		{
			if($countryCode=='EE')
				$code=800599;
			else
				$code=self::getIntegrationCode('foreignOtherInstitutionByCountry', $countryCode, 'Other');
		}

		return $code;
	}

	/**
	 * Find business entity from name
	 * @param string $name the agency, institution or company name
	 * @return string entity
	 */
	protected static function getBusinessEntity($name)
	{
		$entities=array(
			'AS','UÜ','OÜ','TÜH','MTÜ','TÜ','SA','FIE',
			'AKTSIASELTS','TÄISÜHING','USALDUSÜHING','OSAÜHING','MITTETULUNDUSÜHING','TULUNDUSÜHISTU','SIHTASUTUS',
			'Aktsiaselts','Täisühing','Usaldusühing','Osaühing','Mittetulundusühing','Tulundusühistu','Sihtasutus',
			'aktsiaselts','täisühing','usaldusühing','osaühing','mittetulundusühing','tulundusühistu','sihtasutus',
		);

		foreach($entities as $entity)
		{
			if(strstr($name, $entity))
				return $entity;
		}

		return null;
	}

	/**
	 * Find method name by type code
	 * @param integer foreign institution type code
	 * @return string method key
	 */
	protected static function getForeignTypeMethod($typeCode)
	{
		$methods=array(
			self::FOREIGN_TYPE_GOVERNMENT_AGENCY=>'foreignGovernmentAgencyByCountry',
			self::FOREIGN_TYPE_LOCAL_AGENCY=>'foreignLocalAgencyByCountry',
			self::FOREIGN_TYPE_INTERNATIONAL_ORGANISATION=>'foreignInternationalOrganisationByCountry',
			self::FOREIGN_TYPE_FINANCIAL_INSTITUTION=>'foreignFinancialInstitutionByCountry',
			self::FOREIGN_TYPE_OTHER=>'foreignOtherInstitutionByCountry',
		);

		return isset($methods[$typeCode]) ? $methods[$typeCode] : $methods[self::FOREIGN_TYPE_OTHER];
	}

	/**
	 * @param string the method key
	 * @param string the param key
	 * @param string the fallback key
	 * @return string integration code or null
	 */
	protected static function getIntegrationCode($method, $param, $fallback=null)
	{
		$data=static::getData();

		if($param && isset($data[$method][$param]))
			return $data[$method][$param];
		elseif($fallback && isset($data[$method][$fallback]))
			return $data[$method][$fallback];
		else
			return null;
	}
}