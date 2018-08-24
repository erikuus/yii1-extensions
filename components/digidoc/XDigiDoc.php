<?php

/**
 * XDigiDoc class file
 *
 * XDigiDoc component enables to save files into digidoc container using digidoc service
 *
 * XDigiDoc component is meant to be used together with {@link XDigiDocWidget} and {@link XDigiDocAction}.
 * Together these classes provide a solution to digitally sign files with Estonian ID Card and Mobile ID.
 *
 * The following shows how to use the component together with {@link XDigiDocWidget} and {@link XDigiDocAction}.
 *
 * MAIN CONFIGURATION
 * In application main cofiguration file import hashcode-lib (it must be imported before session is started)
 * and define digidoc component.
 *
 * <pre>
 * 'import'=>array(
 *     'ext.components.digidoc.vendor.hashcode-lib.*'
 * ),
 * 'components'=>array(
 *     'digidoc'=> array(
 *         'class'=>'ext.components.digidoc.XDigiDoc',
 *         'url'=>'https://tsp.demo.sk.ee',
 *         'directory'=>'temp/',
 *     )
 * )
 * </pre>
 *
 * CONTROLLER ACTIONS
 * Set up {@link XDigiDocAction} and code sign, success and failure actions according to your needs.
 *
 * <pre>
 * class DigidocController extends Controller
 * {
 *     public function actions()
 *     {
 *         return array(
 *             'signing'=>array(
 *                 'class'=>'ext.components.digidoc.XDigiDocAction',
 *                 'successUrl'=>$this->createUrl('/digidoc/success'),
 *                 'failureUrl'=>$this->createUrl('/digidoc/failure'),
 *                 'mobileServiceName'=>'Testimine',
 *                 'mobileServiceInfo'=>'Sign test document'
 *             )
 *         );
 *     }
 *
 *     public function actionSign()
 *     {
 *         $container='test.bdoc';
 *         $files=array(
 *             'path/to/img.jpg'=>'image/jpeg',
 *             'path/to/doc.pdf'=>'application/pdf'
 *         );
 *         Yii::app()->digidoc->createContainerWithFiles($container, $files);
 *         $this->render('sign');
 *     }
 *
 *     public function actionSuccess()
 *     {
 *         // get directory and container from session
 *         $sessDirectory=Yii::app()->digidoc->getDirectoryFromDdsSession();
 *         $containerName=Yii::app()->digidoc->getContainerNameFromDdsSession();
 *
 *         // define source and destination for copy
 *         $source=$sessDirectory.DIRECTORY_SEPARATOR.$containerName;
 *         $destination='path/to/folder'.DIRECTORY_SEPARATOR.$containerName;
 *
 *         // copy file to permanent location
 *         if(copy($source, $destination))
 *         {
 *             Yii::app()->user->setFlash('success', 'User successfully added a signature.');
 *             Yii::app()->digidoc->killDdsSession();
 *             $link=$destination;
 *         }
 *         else
 *         {
 *             Yii::app()->user->setFlash('failure', 'User successfully added a signature, but copying container failed!');
 *             $link=$source;
 *         }
 *
 *         $this->render('success',array(
 *             'link'=>$link,
 *         ));
 *     }
 *
 *     public function actionFailure()
 *     {
 *         $this->render('failure');
 *     }
 * }
 * </pre>
 *
 * VIEW WIDGET
 * Given the example controller above, {@link XDigiDocWidget} should be called inside 'sign' view.
 * See {@link XDigiDocWidget} for more examples how widget can be configured.
 *
 * <pre>
 * $this->widget('ext.components.digidoc.XDigiDocWidget', array(
 *     'callbackUrl'=>$this->createUrl('/digidoc/signing')
 * ));
 * </pre>
 *
 * IMPORTANT
 * - Signing with ID card requires SSL.
 * - Service name in {@link XDigiDocAction} must be 'Testimine' if using test service https://tsp.demo.sk.ee/
 *
 * @link https://www.id.ee/index.php?id=30279
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

class XDigiDoc extends CApplicationComponent
{
	/**
	 * @var string $url the digidoc service location
	 * Must be one of the following: [https://tsp.demo.sk.ee | https://digidocservice.sk.ee/DigiDocService]
	 * NOTE! Service name in {@link XDigiDocAction} must be 'Testimine' if set to https://tsp.demo.sk.ee/
	 * NOTE! If set to "https://tsp.demo.sk.ee/" for testing, PHP 5.6 throws error "Could not connect to host"
	 * It happens because PHP 5.6 SoapClient makes the first call to the specified WSDL URL and after connecting
	 * to it and receiving the result, it tries to connect to the WSDL specified in the result in: <soap:address location>
	 * As http://www.sk.ee/DigiDocService/DigiDocService_2_3.wsdl set location to "https://digidocservice.sk.ee/DigiDocService"
	 * SOAP Client fails with message "Could not connect to host".
	 * @link https://stackoverflow.com/questions/4318870/soapfault-exception-could-not-connect-to-host
	 */
	public $url;
	/**
	 * @var string $format the digidoc container format
	 * Must be one of the following: [BDOC|DIGIDOC-XML]
	 * Defaults to 'BDOC'
	 */
	public $format='BDOC';
	/**
	 * @var string $version the digidoc container format
	 * Must be 2.1 for BDOC or 1.3 for DIGIDOC-XML
	 * Defaults to '2.1'
	 */
	public $version='2.1';
	/**
	 * @var string the directory wherein session directory will be created for digidoc container
	 * For example, if directory is 'path/to/upload/', container will be saved to 'path/to/upload/1002967812/example.bdoc'
	 * NOTE! Should end with a directory separator
	 */
	public $directory;

	/**
	 * Initializes the component
	 * This method checks if required values are set
	 * and constructs soap client object
	 */
	public function init()
	{
		// require url
		if(!$this->url)
			throw new CException('"url" has to be set!');

		// import vendor classes
		if(!Yii::getPathOfAlias('xdigidoc'))
			Yii::setPathOfAlias('xdigidoc', __DIR__);

		// note that hashcode-lib must be imported
		// in config/main.php before session is started
		Yii::import('xdigidoc.vendor.service.*');
		Yii::import('xdigidoc.vendor.helpers.*');
		Yii::import('xdigidoc.vendor.exceptions.*');
	}

	/**
	 * Create digidoc container with files
	 * @param string $container the filename of or the path to the digidoc container to be created
	 *   If directory is defined as component property, container can be filename
	 * @param array $files the list of files to be saved into digidoc container
	 *   Must use path and mimetype for key value pairs
	 *   For example:
	 *   array(
	 *     'path/to/img.jpg'=>'image/jpeg',
	 *     'path/to/doc.pdf'=>'application/pdf'
	 *   )
	 * @return string $pathToContainer the path to created unsigned container
	 * @throws Exception
	 */
	public function createContainerWithFiles($container, $files)
	{
		if(!$container)
			throw new CException('Container has to be set!');

		// get container filename
		$containerName=basename($container);

		// set directory wherein session directory will be created for digidoc container
		// if directory is not defined as component property extract it from container path
		// note that directory should end with a separator
		$directory=$this->directory ? $this->directory : dirname($container).DIRECTORY_SEPARATOR;

		// get digidoc service instance
		$dds=DigiDocService::instance($this->url);

		// init new digidoc service session
		SessionHelper::initDdsSession($dds, $directory, $containerName);

		// make dir for container
		FileHelper::makeUploadDir();

		// create an empty container to digidoc service session
		$dds->CreateSignedDoc(array(
			'Sesscode'=>SessionHelper::getDdsSessionCode(),
			'Format'=>$this->format,
			'Version'=>$this->version,
		));

		// add data files to the container in digidoc service session
		$fileData=array();
		foreach ($files as $pathToFile=>$mimeType)
		{
			DocHelper::addDatafileViaDds($dds, $pathToFile, $mimeType);
			$fileData[]=new FileSystemDataFile($pathToFile);
		}

		// get the container from digidoc service
		$getSignedDocResponse=$dds->GetSignedDoc(array('Sesscode' => SessionHelper::getDdsSessionCode()));
		$containerData=$getSignedDocResponse['SignedDocData'];
		if (strpos($containerData, 'SignedDoc')===false)
			$containerData = base64_decode($containerData);

		// rewrite the local container with new content
		$pathToContainer=DocHelper::createContainerWithFiles($containerData, $fileData);

		return $pathToContainer;
	}

	/**
	 * Check if there is open session then try to close it and delete session directory
	 * @return void
	 */
	public function killDdsSession()
	{
		// get digidoc service instance
		$dds=DigiDocService::instance($this->url);

		// kill session
		SessionHelper::killDdsSession($dds);
	}

	/**
	 * @return string upload directory from session
	 */
	public function getDirectoryFromDdsSession()
	{
		return SessionHelper::getUploadDirectory();
	}

	/**
	 * @return string container name from session
	 */
	public function getContainerNameFromDdsSession()
	{
		return SessionHelper::getOriginalContainerName();
	}
}