<?php

/**
 * XDokobitIframeWidget class file.
 *
 * XDokobitIframeWidget embeds Dokobit Documents Gateway iframe and javascript that allow to sign documents without leaving website.
 *
 * XDokobitIframeWidget is meant to be used together with {@link XDokobitDocuments} and {@link XDokobitDownloadAction}.
 * These classes provide a unified solution that enables to digitally sign documents through Dokobit Documents Gateway.
 *
 * First define controller action that uploads and prepares files for signing:
 *
 * ```php
 * public function actionSign()
 * {
 *     // set signer
 *     $signers=array();
 *
 *     $signer=array(
 *         'id'=>Yii::app()->user->id,
 *         'name'=>Yii::app()->user->firstname,
 *         'surname'=> Yii::app()->user->lastname
 *     );
 *
 *     array_push($signers, $signer);
 *
 *     // upload file
 *     $files=array();
 *
 *     $file=array(
 *         'name'=>'agreement.pdf',
 *         'digest'=>sha1_file('agreement.pdf'),
 *         'content'=>base64_encode(file_get_contents('agreement.pdf'))
 *     );
 *
 *     $uploadResponse=Yii::app()->dokobitDocuments->uploadFile(array(
 *         'file'=>$file
 *     ));
 *
 * 	   if($uploadResponse['status']=='ok')
 * 	   {
 *         $statusResponse=null;
 *         while($statusResponse===null || $statusResponse['status']=='pending')
 *         {
 *             $statusResponse=Yii::app()->dokobitDocuments->checkFileStatus($uploadResponse['token']);
 *             sleep(2);
 *         }
 *
 *         if($statusResponse['status']=='uploaded')
 *         {
 *             $file['token']=$uploadResponse['token'];
 *             array_push($files, $file);
 *
 *             // create signing
 *             $signingResponse=Yii::app()->dokobitDocuments->createSigning(array(
 *                 'type'=>'asice',
 *                 'name'=>'agreement',
 *                 'language'=>'et',
 *                 'signers'=>$signers,
 *                 'files'=>$files,
 *             ));
 *
 *             if($signingResponse['status']=='ok')
 *             {
 *                 $signingToken=$signingResponse['token'];
 *                 $signerAccessToken=$signingResponse['signers'][Yii::app()->user->id];
 *                 $signingUrl=Yii::app()->dokobitDocuments->getSigningUrl($signingToken, $signerAccessToken);
 *
 *                 // render view
 *                 $this->render('sign', array(
 *                     'signingUrl'=>$signingUrl,
 *                     'signingToken'=>$signingToken,
 *                     'callbackUrl'=>$this->createUrl('dokobitDownload'),
 *                     'callbackToken'=>'abcdefghijklmnoprstuvw'
 *                 ));
 *
 *                 Yii::app()->end();
 *             }
 *             else
 *                 Yii::app()->user->setFlash('failed','Signing could not be created!');
 *         }
 *         else
 *             Yii::app()->user->setFlash('failed','File could not be uploaded!');
 * 	   }
 *     else
 *         Yii::app()->user->setFlash('failed','File could not be uploaded!');
 *
 *     $this->redirect(array('failure'));
 * }
 * ```
 *
 * Inside 'sign' view call widget that embeds iframe and javascript:
 *
 * ```php
 * $this->widget('ext.components.dokobit.documents.XDokobitIframeWidget', array(
 *     'signingUrl'=>$signingUrl,
 *     'signingToken'=>$signingToken,
 *     'callbackUrl'=>$callbackUrl,
 *     'callbackToken'=>$callbackToken
 * ));
 * ```
 *
 * Please refer to README.md for complete usage information.
 *
 * @link https://gateway-sandbox.dokobit.com/api/doc Documentation
 * @link https://support.dokobit.com/category/537-developer-guide Developer guide
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XDokobitIframeWidget extends CWidget
{
	/**
	 * @var string $signingUrl the url to Dokobit Documents Gateway signing page
	 * @see XDokobitDocuments::getSigningUrl()
	 */
	public $signingUrl;
	/**
	 * @var string $signingToken the token returned by Dokobit Documents Gateway API create signing request
	 * @see XDokobitDocuments::createSigning()
	 */
	public $signingToken;
	/**
	 * @var string $callbackUrl the url to the download action that is called after successful signing
	 * @see XDokobitDownloadAction
	 */
	public $callbackUrl;
	/**
	 * @var string $callbackToken the token that will be passed through to download action
	 * @see XDokobitDownloadAction
	 */
	public $callbackToken;
	/**
	 * @var array $htmlOptions the HTML attributes for the iframe tag
	 *
	 * Defaults to
	 *
	 * ```php
	 * array(
	 *     'width'=>'100%',
	 *     'height'=>'500px',
	 *     'frameborder'=>'0'
	 * )
	 * ```
	 */
	public $htmlOptions=array(
		'width'=>'100%',
		'height'=>'500px',
		'frameborder'=>'0'
	);
	/**
	 * @var string $jsUrl the url to dokobit integration javascript that allows to sign documents without leaving website
	 * This script will be added at the bottom of the page before body closing tag
	 * Defaults to 'https://gateway-sandbox.dokobit.com/js/isign.frame.js'
	 */
	public $jsUrl='https://gateway-sandbox.dokobit.com/js/isign.frame.js';
	/**
	 * @var string $resultContainerSelector the jquery selector for tag ...
	 * Defaults to '#result'
	 */
	public $resultContainerSelector='#result';
	/**
	 * @var string $failureMessage the message displayed to user when ajax call to download action throws exception
	 */
	public $failureMessage;
	/**
	 * @var boolean whether the widget is visible
	 * Defaults to true
	 */
	public $visible=true;

	/**
	 * Initializes the widget.
	 */
	public function init()
	{
		if($this->visible)
		{
			// check if required values are set
			if(!$this->signingUrl || !$this->signingToken)
				throw new CException('"signingUrl" and "signingToken" have to be set!');

			// finalize html options
			$this->htmlOptions['id']='isign-gateway';
			$this->htmlOptions['src']=$this->signingUrl;

			// register client scripts
			if($this->callbackUrl)
			{
				$this->registerClientScript();
				$this->registerClientScriptFiles();
			}

			// render iframe open tag
			echo CHtml::openTag('iframe', $this->htmlOptions)."\n";
		}
	}

	/**
	 * Renders the close tag of the iframe.
	 */
	public function run()
	{
		if($this->visible)
			echo CHtml::closeTag('iframe');
	}

	/**
	 * Registers inline client script.
	 */
	protected function registerClientScript()
	{
		$callbackParams=CJavaScript::encode(array(
			'signing_token'=>$this->signingToken,
			'callback_token'=>$this->callbackToken
		));

		Yii::app()->clientScript->registerScript(__CLASS__, "
			Isign.onSignSuccess = function() {
				$.post('$this->callbackUrl', $callbackParams, function(data) {
  					$('$this->resultContainerSelector').html(data);
					window.scrollTo(0,0);
				}).fail(function() {
    				$('#result').html('$this->failureMessage');
					window.scrollTo(0,0);
				});
			};
		", CClientScript::POS_END);
	}

	/**
	 * Registers client script files.
	 */
	protected function registerClientScriptFiles()
	{
		$cs=Yii::app()->clientScript;

		// register core script
		$cs->registerCoreScript('jquery');

		// register dokobit integration javascript
		$cs->registerScriptFile($this->jsUrl, CClientScript::POS_END);
	}
}