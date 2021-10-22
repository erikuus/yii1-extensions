<?php
/**
 * XWebServiceAction class file.
 *
 * XWebServiceAction extends CWebServiceAction calling XWebService class instead of CWebService
 *
 * To use XWsdlGenerator in controller add:
 *
 * public function actions()
 * {
 *     return array(
 *        'service'=>array(
 *             'class'=>'ext.components.webservice.XWebServiceAction',
 *         )
 *     );
 * }
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XWebServiceAction extends CWebServiceAction
{
	protected function createWebService($provider,$wsdlUrl,$serviceUrl)
	{
		return new XWebService($provider,$wsdlUrl,$serviceUrl);
	}
}