<?php
/**
 * XWebService class file.
 *
 * XWebService extends CWebService generating wsdl without any cache
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XWebService extends CWebService
{
	public function generateWsdl()
	{
		$providerClass=is_object($this->provider) ? get_class($this->provider) : Yii::import($this->provider,true);
		$generator=Yii::createComponent($this->generatorConfig);
		$wsdl=$generator->generateWsdl($providerClass,$this->serviceUrl,$this->encoding);
		return $wsdl;
	}
}