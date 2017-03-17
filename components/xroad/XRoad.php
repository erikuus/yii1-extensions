<?php
/**
 * XRoad class file.
 *
 * XRoad component enables to send/receive documents to/from DVK with the help of X-Road
 *
 * X-Road is the backbone of e-Estonia. It's the invisible yet crucial environment that allows
 * the nation's various e-services databases, both in the public and private sector, to link up
 * and operate in harmony.
 *
 * The Document Exchange Center (dokumendivahetuskeskus or DVK in Estonian) is an information system
 * that offers a centralized document exchange service for document management systems and other
 * information systems. The goal of the DVK is to interface separate information systems with the help
 * of the X-road environment, provide short-term document storage and, in the near future, offer services
 * that support document processing.
 *
 * The following shows how to use XRoad component.
 *
 * Configure component:
 * <pre>
 * 'components'=>array(
 *     'xRoad'=> array(
 *         'class'=>'ext.components.xroad.XRoad',
 *         'institutionRegNr'=>'12345678',
 *         'serverIP'=>'123.45.678.999'
 *     ),
 * )
 * </pre>
 *
 * Receive documents
 * <pre>
 * Yii::app()->xRoad->officialID='EE123456789';
 * $xml=Yii::app()->xRoad->receiveDocuments();
 * </pre>
 *
 * NOTE! Sending documents is not tested yet!
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XRoad extends CApplicationComponent
{
	/**
	 * @var string the register code of institution that consumes X-Road service
	 */
	public $institutionRegNr;
	/**
	 * @var string the national identification number (prefixed with country code) of the official that consumes X-Road service
	 */
	public $officialID;
	/**
	 * @var string the fullname of the official that consumes X-Road service
	 */
	public $officialFullname;
	/**
	 * @var string the secure proxy server IP address
	 */
	public $serverIP;

	/**
	 * Initializes the component.
	 * This method checks if required values are set
	 */
	public function init()
	{
		if(!$this->institutionRegNr)
			throw new CException('"institutionRegNr" has to be set!');

		if(!$this->serverIP)
			throw new CException('"serverIP" has to be set!');
	}

	/**
	 * Send documents
	 * @param string $folder the target folder name, defaults to '/' meaning root folder
	 * @param string $document
	 * @return mixed the xml string on success, false on failure
	 */
	public function sendDocuments($folder='/', $document=null)
	{
		$soapBody = $this->fetch('sendDocuments', array('kaust'=>$folder));
		return $this->dhlRequest($soapBody, 'dhl.sendDocuments.v1', $document);
	}

	/**
	 * Receive documents
	 * @param $folder The target folder name, defaults to '/' meaning root folder
	 * @return mixed the xml string on success, false on failure
	 */
	public function receiveDocuments($folder='/')
	{
		$soapBody = $this->fetch('receiveDocuments', array('kaust'=>$folder));
		return $this->dhlRequest($soapBody, 'dhl.receiveDocuments.v1');
	}

	/**
	 * Mark documents as received
	 * @param string $id the id of the document repository
	 * @return mixed 'OK' on success, false on failure
	 */
	public function markDocumentsReceived($id)
	{
		$soapBody = $this->fetch('markDocumentsReceived');
		$document = $this->fetch('item', array('dhl_id'=>$id));
		$response = $this->dhlRequest($soapBody, 'dhl.markDocumentsReceived.v2', $document);

		if($response && $response->getElementsByTagName('keha')->item(0))
			return $response->getElementsByTagName('keha')->item(0)->nodeValue;
		else
			return false;
	}

	/**
	 * Send DHL request
	 * @param string $soapBody
	 * @param string $serviceName service name [dhl.sendDocuments.v1 | dhl.receiveDocuments.v1 | dhl.markDocumentsReceived.v2]
	 * @param string $document
	 * @return mixed the xml string on success, false on failure
	 */
	protected function dhlRequest($soapBody, $serviceName, $document=null)
	{
		// Service name consists of repository.query.version
		$serviceParts=explode('.', $serviceName);

		$soapEnvelope = $this->fetch('dhlRequest',array(
			'asutus'=>$this->institutionRegNr,
			'andmekogu'=>$serviceParts[0],
			'isikukood'=>$this->officialID,
			'ametniknimi'=>$this->officialFullname,
			'id'=>md5(time()),
			'nimi'=>$serviceName,
			'soapBody'=>$soapBody,
		));

		return $this->xRoadRequest($soapEnvelope, $document);
	}

	/**
	 * Send XRoad request
	 * @param string $soapEnvelope
	 * @param string $document
	 * @return mixed the xml string on success, false on failure
	 */
	protected function xRoadRequest($soapEnvelope, $document=null)
	{
		if($document)
		{
			$boundary = '=_'.md5(time());
			$contentType = 'multipart/related; type="text/xml"; boundary="'.$boundary.'"';

			$post = "--$boundary";
			$post.= "\r\nContent-Type:text/xml; charset=\"UTF-8\"";
			$post.= "\r\nContent-Transfer-Encoding:8bit\r\n\r\n$soapEnvelope\r\n";
			$post.= "\r\n--$boundary";
			$post.= "\r\nContent-Disposition:php5hmCiX";
			$post.= "\r\nContent-Type:{http://www.w3.org/2001/XMLSchema}base64Binary";
			$post.= "\r\nContent-Transfer-Encoding:base64";
			$post.= "\r\nContent-Encoding: gzip";
			$post.= "\r\nContent-ID:<b8fdc418df27ba3095a2d21b7be6d802>\r\n\r\n".base64_encode(gzencode($document))."\r\n--$boundary--";
		}
		else
		{
			$post = $soapEnvelope;
			$contentType = 'text/xml';
		}

		$context = array(
			'http'=>array(
				'method'=>'POST',
				'header'=> "Content-type: $contentType",
				'content' => $post
			)
		);

		$context = stream_context_create($context);

		$handle = fopen('http://'.$this->serverIP.'/cgi-bin/consumer_proxy', 'r', false, $context);

		if ($handle)
		{
			$content = '';
			while (!feof($handle))
			  $content .= fread($handle, 4096);
			fclose($handle);

			$doc = new DOMDocument();
			if(@$doc->loadXML($content))
			{
				$body = $doc->getElementsByTagNameNS('http://schemas.xmlsoap.org/soap/envelope/','Body');
				$fault = $body->item(0)->getElementsByTagNameNS('http://schemas.xmlsoap.org/soap/envelope/','Fault');
				if($fault->item(0))
				{
					if (YII_DEBUG)
					{
						$faultcode = $fault->item(0)->getElementsByTagName('faultcode')->item(0)->nodeValue;
						$faultstring = $fault->item(0)->getElementsByTagName('faultstring')->item(0)->nodeValue;
						$faultactor = $fault->item(0)->getElementsByTagName('faultactor')->item(0)->nodeValue;
						throw new CException("Code:{$faultcode} Massage:{$faultstring} Actor:{$faultactor}");
					}
					else
						return false;
				}
				else
					return $body->item(0);
			}
			else
				return $this->extractBody($content);
		}
		else
			return false;
	}

	/**
	 * @param string that is base64 encoded and gzipped
	 * @return string that is base64 decoded and ungzipped
	 */
	protected function extractBody($str)
	{
		$needle = "Content-Encoding: gzip\r\n\r\n";
		$str = substr($str, strpos($str, $needle) + strlen($needle));
		$str = substr($str, 0, strpos($str,"\r\n--"));
		$str = $this->gzdecode(base64_decode($str));

		return $str;
	}

	/**
	 * Decodes a gzip compressed string
	 */
	protected function gzdecode($data)
	{
		if (function_exists("gzdecode"))
			return gzdecode($data);
		else
			return gzinflate(substr($data, 10, -8));
	}

	/**
	 * Fetch xml template
	 * @param string $tpl template name (file name without extension)
	 * @param array $data data to be passed to the template
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
?>
