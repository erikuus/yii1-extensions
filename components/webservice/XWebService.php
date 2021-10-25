<?php
/**
 * XWebService class file.
 *
 * XWebService extends CWebService generating wsdl from file
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XWebService extends CWebService
{
	public function generateWsdl()
	{
		return file_get_contents($this->wsdlUrl);
	}
}