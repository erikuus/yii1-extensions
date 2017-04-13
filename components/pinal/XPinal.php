<?php
/**
 * XPinal class file.
 *
 * XPinal component enables to register documents and send files to Pinal
 *
 * Pinal is the document management system in the Ministry of Education and Research in the Republic of Estonia.
 *
 * The following shows how to use XPinal component.
 *
 * Configure component:
 * <pre>
 * 'components'=>array(
 *     'pinal'=> array(
 *         'class'=>'ext.components.pinal.XPinal',
 *         'soapWSDL'=>'https://pinal.hm.ee/_vti_bin/RMService.svc?wsdl',
 *         'soapOptions'=>array(
 *             'login'=>'some_name',
 *             'password'=>'some_password',
 *             'cache_wsdl'=>WSDL_CACHE_NONE,
 *             'cache_ttl'=>86400,
 *             'trace'=>true,
 *             'exceptions'=>true,
 *         )
 *     )
 * )
 * </pre>
 *
 * ADD DOCUMENT
 * <pre>
 * $parentReferenceCode='1-16/17';
 * $documentName='Ebaolulise komisjoni protokoll';
 * $documentType='RA Protokoll';
 * $metadata=array(
 *     'Komisjoni toimumise kuupaev'=>'2017-03-17',
 *     'Komisjoni nimetus'=>'Ebaoluline komisjon',
 *     'Koosoleku juhataja'=>'Erik Uus',
 *     'RMAccessRestrictionLevel'=>'Avalik'
 * );
 * $importXml=Yii::app()->pinal->getImportXml($parentReferenceCode, $documentName, $documentType, $metadata);
 * $restult=Yii::app()->pinal->capture($importXml);
 * $document=$restult->CaptureResult->createdDocuments->document;
 * echo $document->id;
 * echo $document->parentItemId;
 * echo $document->referenceCode;
 * </pre>
 *
 * UPDATE DOCUMENT
 * <pre>
 * $parentReferenceCode='1-16/17';
 * $mergeReferenceCode='1-16/17/14';
 * $documentName='Olulise komisjoni protokoll';
 * $documentType='RA Protokoll';
 * $metadata=array(
 *     'Komisjoni nimetus'=>'Oluline komisjon',
 * );
 * $importXml=Yii::app()->pinal->getImportXml($parentReferenceCode, $documentName, $documentType, $metadata, $mergeReferenceCode);
 * $restult=Yii::app()->pinal->capture($importXml);
 * $document=$restult->CaptureResult->createdDocuments->document;
 * echo $document->id;
 * echo $document->parentItemId;
 * echo $document->referenceCode;
 * </pre>
 *
 * ADD FILE
 * <pre>
 * $documentId=1871;
 * $fileName='protokoll.pdf';
 * $mimeType='application/pdf';
 * $fileContent=file_get_contents('path/to/protokoll.pdf');
 * $restult=Yii::app()->pinal->addFile($documentId, $fileName, $mimeType, $fileContent)
 * if (is_object($restult)) echo 'File was successfully added!';
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(__FILE__).'/vendor/NTLMStream.php';
require_once dirname(__FILE__).'/vendor/NTLMSoapClient.php';

class XPinal extends CApplicationComponent
{
 	/**
 	 * @var boolean whether to register NTLM sream wrapper
 	 * If wsdl and xsd files are behind NTLM Authentication we
 	 * need to register NTLM sream wrapper for SoapClient to work.
 	 */
	public $registerWrapper=true;

	/**
	 * @var string the URI of the WSDL file or NULL if working in non-WSDL mode
	 */
	public $soapWSDL;

	/**
	 * @var an array of soap client options
	 * Note that 'login' and 'password' are required.
	 * Also note that 'ssl' stream context may be required for PHP 5.6+
	 * For example:
	 * $context = array(
	 *     'ssl' => array(
	 *         'ciphers'=>'RC4-SHA',
	 *         'verify_peer'=>false,
	 *         'verify_peer_name'=>false
	 *     )
	 * );
	 * $soapOptions = array(
	 *     'login'=>'some_name',
	 *     'password'=>'some_password',
	 *     'cache_wsdl'=>WSDL_CACHE_NONE,
	 *     'cache_ttl'=>86400,
	 *     'trace'=>true,
	 *     'exceptions'=>true,
	 *     'stream_context' => stream_context_create($context)
	 * );
	 */
	public $soapOptions=array();

	/**
	 * @var string url that points to Pinal document view
	 */
	public $viewUrl;

	/**
	 * @var NTLMSoapClient object
	 */
	private $_soapClient;

	/**
	 * Initializes the component.
	 * This method checks if required values are set
	 * and constructs soap client object
	 */
	public function init()
	{
		if(!$this->soapWSDL)
			throw new CException('"soapWSDL" has to be set!');

		if($this->soapOptions===array())
			throw new CException('"soapOptions" has to be set!');
	}

	/**
	 * Get soap client
	 * @return soap client object
	 */
	public function getClient()
	{
		if($this->_soapClient===null)
		{
			if($this->registerWrapper)
			{
				stream_wrapper_unregister('https');
				stream_wrapper_register('https', 'PinalNTLMStream') or die('Failed to register protocol');
			}

			$this->_soapClient=new NTLMSoapClient($this->soapWSDL, $this->soapOptions);

			if($this->registerWrapper)
				stream_wrapper_restore('https');
		}

		return $this->_soapClient;
	}

	/**
	 * Checks whether services work
	 * @return stdClass object
	 */
	public function doesItWork()
	{
		return $this->getClient()->DoesItWork();
	}

	/**
	 * Add, update and register documents.
	 *
	 * @param string $importXml the xml content that defines what and where to create or update.
	 *
	 * For example, document can be created as follows:
	 * <?xml version="1.0" encoding="utf-8"?>
	 * <system schemaVersion="1" xmlns="http://www.nortal.com/FlairPoint/RecordsManagement/WebCapture">
	 *     <hierarchy>
	 *         <parent>
	 *             <referenceCode>1-16/17</referenceCode>
	 *         </parent>
	 *         <children>
	 *             <document name="Ebaolulise komisjoni protokoll" contentType="RA Protokoll">
	 *                 <metadata>
	 *                     <field name="Komisjoni toimumise kuupaev" value="2017-03-17"/>
	 *                     <field name="Komisjoni nimetus" value="Ebaoluline komisjon"/>
	 *                     <field name="Koosoleku juhataja" value="Erik Uus"/>
	 *                     <field name="RMAccessRestrictionLevel" value="Avalik"/>
	 *                 </metadata>
	 *             </document>
	 *         </children>
	 *     </hierarchy>
	 * </system>
	 *
	 * And later this document can be updated as follows:
	 * <?xml version="1.0" encoding="utf-8"?>
	 * <system schemaVersion="1" xmlns="http://www.nortal.com/FlairPoint/RecordsManagement/WebCapture">
	 *     <hierarchy>
	 *         <parent>
	 *             <referenceCode>1-16/17</referenceCode>
	 *         </parent>
	 *         <children>
	 *             <document name="Olulise komisjoni protokoll" contentType="RA Protokoll" merge="referenceCode" referenceCode="1-16/17/14">
	 *                 <metadata>
	 *                     <field name="Komisjoni nimetus" value="Oluline komisjon"/>
	 *                 </metadata>
	 *             </document>
	 *         </children>
	 *     </hierarchy>
	 * </system>
	 *
	 * @param string $registerAfterCapture whether to register document ['true'|'false']. Defaults to 'true'
	 *
	 * @return stdClass object; you can get relevant data from returned object as follows:
	 * $document=$return->CaptureResult->createdDocuments->document;
	 * echo $document->id;
	 * echo $document->parentItemId;
	 * echo $document->referenceCode;
	 */
	public function capture($importXml, $registerAfterCapture='true')
	{
		$captureXml=$this->fetch('capture', array(
			'importXml'=>$importXml,
			'registerAfterCapture'=>$registerAfterCapture
		));

		try
		{
			$params=new SoapVar($captureXml, XSD_ANYXML);
			return $this->getClient()->Capture($params);
		}
		catch(SoapFault $fault)
		{
			return $fault;
		}
	}

	/**
	 * Receive documents
	 * @param string $documentId the id of the target document
	 * @param string $fileName the name of file to be sent
	 * @param string $mimeType the mime type of file to be sent
	 * @param string $fileContent the content of file to be sent
	 * @return stdClass object; you can check if sending file
	 * succeeded by checking is_object($return)
	 */
	public function addFile($documentId, $fileName, $mimeType, $fileContent)
	{
		$addFileXml=$this->fetch('addFile', array(
			'documentId'=>$documentId,
			'fileName'=>$fileName,
			'mimeType'=>$mimeType,
			'fileContent'=>base64_encode($fileContent)
		));

		try
		{
			$params=new SoapVar($addFileXml, XSD_ANYXML);
			return $this->getClient()->AddFile($params);;
		}
		catch(SoapFault $fault)
		{
			return $fault;
		}
	}

	/**
	 * Get importXML for capture to add or update document.
	 * This is helper function that can be used to compose importXml
	 * that can be sent to capture method in order to create or update document
	 * @param string $parentReferenceCode the reference code of the dossier
	 * @param string $documentName the name of document to be created
	 * @param string $documentType the type of document to be created
	 * @param mixed $metadata; for example, this can be either xml:
	 * <metadata>
	 *     <field name="Komisjoni toimumise kuupaev" value="2017-03-17"/>
	 *     <field name="Komisjoni nimetus" value="Ebaoluline komisjon"/>
	 *     <field name="Koosoleku juhataja" value="Erik Uus"/>
	 *     <field name="RMAccessRestrictionLevel" value="Avalik"/>
	 * </metadata>
	 * or array(
	 *     'Komisjoni toimumise kuupaev'=>'2017-03-17',
	 *     'Komisjoni nimetus'=>'Ebaoluline komisjon',
	 *     'Koosoleku juhataja'=>'Erik Uus',
	 *     'RMAccessRestrictionLevel'=>'Avalik'
	 * );
	 * @param string $mergeReferenceCode the reference code of the document to be updated;
	 * if null, document is added, not updated; defaults to null
	 * @param boolean $case whether to update document identified with $mergeReferenceCode
	 * or to add new subdocument inside same case; if true, new subdocument is added;
	 * defaults to false
	 * @return string xml
	 */
	public function getImportXml($parentReferenceCode, $documentName, $documentType, $metadata, $mergeReferenceCode=null, $case=false)
	{
		if(is_array($metadata))
			$metadata=$this->convertMetadata($metadata);

		if($mergeReferenceCode && $case)
			$tpl='addCaseDocument';
		elseif($mergeReferenceCode)
			$tpl='updateDocument';
		else
			$tpl='addDocument';

		return '<?xml version="1.0" encoding="utf-8"?>'.$this->fetch($tpl, array(
			'parentReferenceCode'=>$parentReferenceCode,
			'mergeReferenceCode'=>$mergeReferenceCode,
			'documentName'=>$documentName,
			'documentType'=>$documentType,
			'metadata'=>$metadata,
		));
	}

	/**
	 * Convert metadata array into xml
	 * @param array $metadata; for example:
	 * array(
	 *     'Komisjoni toimumise kuupaev'=>'2017-03-17',
	 *     'Komisjoni nimetus'=>'Ebaoluline komisjon',
	 *     'Koosoleku juhataja'=>'Erik Uus',
	 *     'RMAccessRestrictionLevel'=>'Avalik'
	 * );
	 * @return string xml; for example:
	 * <metadata>
	 *     <field name="Komisjoni toimumise kuupaev" value="2017-03-17"/>
	 *     <field name="Komisjoni nimetus" value="Ebaoluline komisjon"/>
	 *     <field name="Koosoleku juhataja" value="Erik Uus"/>
	 *     <field name="RMAccessRestrictionLevel" value="Avalik"/>
	 * </metadata>
	 */
	protected function convertMetadata($metadata)
	{
		$xml='<metadata>';
		foreach($metadata as $name => $value)
			$xml.='<field name="'.$name.'" value="'.$value.'"/>';
		$xml.='</metadata>';
		return $xml;
	}

	/**
	 * Fetch xml template
	 * @param string $tpl template name (file name without extension)
	 * @param array $data data to be passed to the template
	 * @return string xml
	 */
	protected function fetch($tpl, $data=array())
	{
		$app=Yii::app();
		$file=dirname(__FILE__).DIRECTORY_SEPARATOR.'tpl'.DIRECTORY_SEPARATOR.$tpl.'.php';

		if($app instanceof CWebApplication)
			return $app->controller->renderFile($file, $data, true);
		else // CConsoleApplication
			return $app->command->renderFile($file, $data, true); // get console application command is available since 1.1.14
	}
}

class PinalNTLMStream extends NTLMStream
{
	protected $login;
	protected $password;

	public function __construct()
	{
		if(Yii::app()->hasComponent('pinal'))
			$this->login=Yii::app()->pinal->soapOptions['login'];

		if(Yii::app()->hasComponent('pinal'))
			$this->password=Yii::app()->pinal->soapOptions['password'];
	}
}