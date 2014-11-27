<?php
/**
 * XWsdlGenerator class file.
 *
 * XWsdlGenerator extends CWsdlGenerator enabling to return data type base64binary
 *
 * To use XWsdlGenerator in controller add:
 *
 * public function actions()
 * {
 *     return array(
 *        'service'=>array(
 *             'class'=>'CWebServiceAction',
 *             'serviceOptions'=>array(
 *                 'generatorConfig'=>'ext.components.webservice.XWsdlGenerator'
 *             )
 *         )
 *     );
 * }
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XWsdlGenerator extends CWsdlGenerator
{
	public function __construct()
	{
		self::$typeMap['base64Binary'] = 'xsd:base64Binary';
	}
}