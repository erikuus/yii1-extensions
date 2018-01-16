<?php
/**
 * ErplyHelper class
 *
 * This class adds helper methods to get integration codes (TP) for ERPLY customer.
 *
 * First import it:
 * ```
 * 'import'=>array(
 *   'ext.components.erply.ErplyHelper',
 * ),
 * ```
 *
 * Then use it as follows:
 * ```
 * $code=ErplyHelper::getPersonIntegrationCode('EE');
 * ```
 *
 * @link extensions/components/erply/vendor/tps.data.php
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class ErplyHelper
{
	const FOREIGN_TYPE_GOVERMENT_AGENCY=1;
	const FOREIGN_TYPE_LOCAL_AGENCY=2;
	const FOREIGN_TYPE_INTERNATIONAL_ORGANISATION=3;
	const FOREIGN_TYPE_FINANCIAL_INSTITUTION=4;
	const FOREIGN_TYPE_OTHER=5;

	/**
	 * Get private person integration code
	 * @param string $countryCode the two char country code (ex. 'EE')
	 * @return string integration code
	 */
	public static function getPersonIntegrationCode($countryCode)
	{
		return $this->getIntegrationCode('privatePersonByCountry', $countryCode, 'Other');
	}

	/**
	 * Get company or institution integration code
	 * @param string $countryCode the two char country code (ex. 'EE')
	 * @param string $countryCode the two char country code (ex. 'EE')
	 * @return string integration code
	 */
	public static function getCompanyCode($name, $countryCode, $regCode, $typeCode)
	{
		return $this->getIntegrationCode('privatePersonByCountry', $countryCode, 'Other');
	}

	/**
	 * @return array options for fo
	 */
	public static function getForeignTypeOptions()
	{
		return array(
			self::FOREIGN_TYPE_GOVERMENT_AGENCY=>Yii::t('XErply.erply', 'Goverment Agency'),
			self::FOREIGN_TYPE_LOCAL_AGENCY=>Yii::t('XErply.erply', 'Local Goverment Agency'),
			self::FOREIGN_TYPE_INTERNATIONAL_ORGANISATION=>Yii::t('XErply.erply', 'International Organisation'),
			self::FOREIGN_TYPE_FINANCIAL_INSTITUTION=>Yii::t('XErply.erply', 'Financial Institution'),
			self::FOREIGN_TYPE_OTHER=>Yii::t('XErply.erply', 'Other'),
		);
	}

	/**
	 * @return array options
	 */
	protected function getForeignTypeMethods()
	{
		return array(
			self::FOREIGN_TYPE_GOVERMENT_AGENCY=>'foreignGovermentAgencyByCountry',
			self::FOREIGN_TYPE_LOCAL_AGENCY=>'foreignLocalAgencyByCountry',
			self::FOREIGN_TYPE_INTERNATIONAL_ORGANISATION=>'foreignInternationalOrganisationByCountry',
			self::FOREIGN_TYPE_FINANCIAL_INSTITUTION=>'foreignFinancialInstitutionByCountry',
			self::FOREIGN_TYPE_OTHER=>'foreignOtherInstitutionByCountry',
		);
	}

	/**
	 * Set the expiration date to one hour ago
	 * @param string the method key
	 * @param string the param key
	 * @param string the fallback key
	 * @return mixed integration code(s)
	 */
	protected function getIntegrationCode($method, $param=null, $fallback=null)
	{
		$data = include dirname(__FILE__).'/vendor/tps.data.php';

		if($param && isset($data[$method][$param]))
			return $data[$method][$param];
		elseif($fallback && $data[$method][$fallback])
			return $data[$method][$fallback];
		elseif(!$param && isset($data[$method]))
			return $data[$method];
		else
			return null;
	}
}