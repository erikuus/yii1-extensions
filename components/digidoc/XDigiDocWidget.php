<?php

/**
 * XDigiDocWidget class file
 *
 * XDigiDocWidget registers client scripts and displays HTML needed to digitally sign files using digidoc service
 *
 * XDigiDocWidget component is meant to be used together with {@link XDigiDoc} and {@link XDigiDocAction}.
 * Together these classes provide a solution to digitally sign files with Estonian ID Card and Mobile ID.
 *
 * See {@link XDigiDoc} for complete example how to configure all components to work together as one solution.
 *
 * The following shows how to use XDigiDocWidget.
 *
 * BASIC EXAMPLE
 * minimal configuration
 *
 * <pre>
 * $this->widget('ext.components.digidoc.XDigiDocWidget', array(
 *     'callbackUrl'=>$this->createUrl('/digidoc/signing')
 * ));
 * </pre>
 *
 * TEMPLATE EXAMPLE
 * template and style customizations
 *
 * <pre>
 * $this->widget('ext.components.digidoc.XDigiDocWidget', array(
 *     'callbackUrl'=>$this->createUrl('/digidoc/signing'),
 *     'template'=>'<h1>{cardTitle}</h1><p>{cardSign}</p><h1>{mobileTitle}</h1><p>{mobileSign}</p>',
 *     'buttonCssClass'=>'btn btn-primary'
 * ));
 * </pre>
 *
 * TABS BASIC EXAMPLE
 * signing forms are displayed in separate tabs
 *
 * <pre>
 * $this->widget('ext.components.digidoc.XDigiDocWidget', array(
 *     'callbackUrl'=>$this->createUrl('/digidoc/signing'),
 *     'layout'=>'tabs'
 * ));
 * </pre>
 *
 * TABS ADVANCED EXAMPLE
 * id card form fields disabled and style customized
 *
 * <pre>
 * $this->widget('ext.components.digidoc.XDigiDocWidget', array(
 *     'callbackUrl'=>$this->createUrl('/digidoc/signing'),
 *     'layout'=>'tabs',
 *     'enableCardSignFields'=>false,
 *     'buttonCssClass'=>'btn btn-primary',
 *     'tabsHtmlOptions'=>array(
 *         'style'=>'width: 440px'
 *     )
 * ));
 * </pre>
 *
 * MODALS BASIC EXAMPLE
 * buttons open signing forms in modals
 *
 * <pre>
 * $this->widget('ext.components.digidoc.XDigiDocWidget', array(
 *     'callbackUrl'=>$this->createUrl('/digidoc/signing'),
 *     'layout'=>'modals',
 *     'buttonCssClass'=>'btn btn-primary',
 * ));
 * </pre>
 *
 * MODALS ADVANCED EXAMPLE
 * id card form fields disabled, images enabled, style and modal customized
 *
 * <pre>
 * $this->widget('ext.components.digidoc.XDigiDocWidget', array(
 *     'callbackUrl'=>$this->createUrl('/digidoc/signing'),
 *     'layout'=>'modals',
 *     'buttonCssClass'=>'btn btn-primary',
 *     'enableCardSignFields'=>false,
 *     'enableImages'=>true,
 *     'mobileModalOptions'=>array(
 *         'modal'=>false,
 *         'width'=>450,
 *         'height'=>300
 *     )
 * ));
 * </pre>
 *
 * @link https://www.id.ee/index.php?id=30279
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XDigiDocWidget extends CWidget
{
	/**
	 * @var string $callbackUrl the URL to which the ajax/post requests are sent during and after the signing process.
	 * Must point to XDigiDocAction
	 * @see XDigiDocAction
	 */
	public $callbackUrl;
	/**
	 * @var string $csrfToken the random token used to perform CSRF validation.
	 */
	public $csrfToken;
	/**
	 * @var mixed $cssFile the CSS file used for the widget. Defaults to null, meaning
	 * using the default CSS file included together with the widget.
	 * If false, no CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 */
	public $cssFile;
	/**
	 * @var string $buttonCssClass the css class for buttons
	 */
	public $buttonCssClass;
	/**
	 * @var string the $layout template used to rende rcardSign and mobileSign views
	 * May be one of the following: [tabs|modals]
	 * Note that id card modal is displayed only if $enableCardSignForm is set to true.
	 */
	public $layout;
	/**
	 * @var string $template the template used to render cardSign and mobileSign views
	 * Defaults to '<b>{cardTitle}</b><p>{cardSign}</p><b>{mobileTitle}</b><p>{mobileSign}</p>'.
	 * Note that this is used only if layout is not set.
	 */
	public $template='<b>{cardTitle}</b><p>{cardSign}</p><b>{mobileTitle}</b><p>{mobileSign}</p>';
	/**
	 * @var string $modalButtonsTemplate the template used to render cardSign and mobileSign buttons
	 * Note that this is used only if layout is set to 'modals'.
	 */
	public $modalButtonsTemplate='{cardSignBtn} {mobileSignBtn}';
	/**
	 * @var array $tabsHtmlOptions the HTML options for CTabView
	 * Note that this is used only if layout is set to 'tabs'.
	 */
	public $tabsHtmlOptions=array();
	/**
	 * @var array $cardModalOptions the options for card sign zii.widgets.jui.CJuiDialog
	 * Note that this is used only if layout is set to 'modals'.
	 */
	public $cardModalOptions=array();
	/**
	 * @var array $mobileModalOptions the options for mobile sign zii.widgets.jui.CJuiDialog
	 * Note that this is used only if layout is set to 'modals'.
	 */
	public $mobileModalOptions=array();
	/**
	 * @var boolean $enableCardSign whether to enable signing the document with ID Card
	 * Defaults to true.
	 */
	public $enableCardSign=true;
	/**
	 * @var boolean $enableMobileSign whether to enable signing the document with ID Card
	 * Defaults to true.
	 */
	public $enableMobileSign=true;
	/**
	 * @var boolean $enableCardSignFields whether to display form fields for signing the document with ID Card
	 * Defaults to true.
	 */
	public $enableCardSignFields=true;
	/**
	 * @var boolean $enableImages whether to display images instead of buttons
	 * Defaults to false.
	 * Note that this is used only if layout is set to 'modals'.
	 */
	public $enableImages=false;
	/**
	 * @var boolean $testEnvironmentInfo whether to display information about digidoc test enviroment
	 * Defaults to false.
	 */
	public $testEnvironmentInfo=false;
	/**
	 * @var string $mobilePhoneNumber the signer mobile phone number
	 * This is used only to prefill mobile sign form
	 */
	public $signerMobilePhoneNumber;
	/**
	 * @var string $idCode the signer Estonian id code (social security number)
	 * This is used only to prefill mobile sign form
	 */
	public $signerIdCode;

	private $_assets;
	private $_cardSign;
	private $_mobileSign;

	/**
	 * Initializes the widget.
	 * Publish and register client files
	 */
	public function init()
	{
		if(!$this->callbackUrl)
			throw new CException('"callbackUrl" have to be set!');

		if($this->layout && !in_array($this->layout, array('modals','tabs')))
			throw new CException('"layout" must be one of the following: [modals|tabs]!');
	}

	/**
	 * Render widget input.
	 */
	public function run()
	{
		// publish and register client script
		$this->registerClientScript();
		$this->registerClientScriptFiles();

		// get card sign view
		if($this->enableCardSign)
		{
			$this->_cardSign=$this->render('cardSignForm', array(
				'enableCardSignFields'=>$this->enableCardSignFields,
				'buttonCssClass'=>$this->buttonCssClass,
				'csrfToken'=>$this->csrfToken
			), true);
		}

		// get mobile sign view
		if($this->enableMobileSign)
		{
			$this->_mobileSign=$this->render('mobileSignForm', array(
				'buttonCssClass'=>$this->buttonCssClass,
				'csrfToken'=>$this->csrfToken,
				'idCode'=>$this->signerIdCode,
				'mobilePhoneNumber'=>$this->signerMobilePhoneNumber
			), true);
		}

		// display test enviroment info
		if($this->testEnvironmentInfo)
			$this->render('testEnvironmentInfo');

		// display views
		switch ($this->layout) {
			case 'modals':
				$this->renderModals();
				break;
			case 'tabs':
				$this->renderTabs();
				break;
			default:
				$this->renderTemplate();
				break;
		}
	}

	/**
	 * Register necessary inline client script.
	 */
	protected function registerClientScript()
	{
		Yii::app()->clientScript->registerScript(__CLASS__, "
			ee.sk.hashcode.defaultPath='{$this->callbackUrl}';
			ee.sk.hashcode.phoneNumberIsMandatory='".Yii::t('XDigiDocWidget.digidoc', 'Phone number is mandatory!')."';
			ee.sk.hashcode.socialSecurityNumberIsMandatory='".Yii::t('XDigiDocWidget.digidoc', 'Social security number is mandatory!')."';
			ee.sk.hashcode.mobileSignIsInProgressMessage='".Yii::t('XDigiDocWidget.digidoc', '<b>Sending digital signing request to phone is in progress.</b> Make sure control code matches with one in the phone screen and enter Mobile-ID PIN2. Control code: ')."';
			ee.sk.hashcode.mobileSignAjaxErrorMessage='".Yii::t('XDigiDocWidget.digidoc', 'There was an error performing AJAX request to initiate MID signing: ')."';
			ee.sk.hashcode.noBackendMessage='".Yii::t('XDigiDocWidget.digidoc', 'Cannot find ID-card browser extensions!')."';
			ee.sk.hashcode.userCancelMessage='".Yii::t('XDigiDocWidget.digidoc', 'Signing canceled by user!')."';
			ee.sk.hashcode.invalidArgumentMessage='".Yii::t('XDigiDocWidget.digidoc', 'Invalid argument!')."';
			ee.sk.hashcode.noCertificatesMessage='".Yii::t('XDigiDocWidget.digidoc', 'Failed reading ID-card certificates! Make sure ID-card reader or ID-card is inserted correctly.')."';
			ee.sk.hashcode.noImplementationMessage='".Yii::t('XDigiDocWidget.digidoc', 'Please install or update ID-card Utility or install missing browser extension!')."';
			ee.sk.hashcode.unknownTechnicalErrorMessage='".Yii::t('XDigiDocWidget.digidoc', 'Unknown technical error occurred!')."';
			ee.sk.hashcode.unknownErrorMessage='".Yii::t('XDigiDocWidget.digidoc', 'Make sure ID-card is inserted correctly! Only then press button.')."';
		", CClientScript::POS_END);
	}

	/**
	 * Publish and register necessary client script files.
	 */
	protected function registerClientScriptFiles()
	{
		$cs=Yii::app()->clientScript;

		// register core script
		$cs->registerCoreScript('jquery');

		// publish and register assets file
		$this->_assets=Yii::app()->assetManager->publish(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets');

		// register css file
		if($this->cssFile===null)
			$cs->registerCssFile($this->_assets.'/css/digidoc.css');
		else if($this->cssFile!==false)
			$cs->registerCssFile($this->cssFile);

		// register js files
		$cs->registerScriptFile($this->_assets. '/js/hwcrypto.js');
		$cs->registerScriptFile($this->_assets. '/js/hashcode.js');
	}

	/**
	 * Render content by CJuiDialog
	 */
	protected function renderModals()
	{
		// buttons
		$this->renderModalButtons();

		// card sign modal
		$this->beginWidget('zii.widgets.jui.CJuiDialog', array(
			'id'=>'cardSignModal',
			'options'=>array_merge(
				array(
					'title'=>Yii::t('XDigiDocWidget.digidoc', 'Sign the document with ID Card'),
					'autoOpen'=>false,
					'modal'=>true,
					'width'=>$this->enableCardSignFields ? 600 : 400,
					'height'=>$this->enableCardSignFields ? 460 : 200,
				),
				$this->cardModalOptions
			)
		));
			echo $this->_cardSign;
		$this->endWidget('zii.widgets.jui.CJuiDialog');

		// mobile sign modal
		$this->beginWidget('zii.widgets.jui.CJuiDialog', array(
			'id'=>'mobileSignModal',
			'options'=>array_merge(
				array(
					'title'=>Yii::t('XDigiDocWidget.digidoc', 'Sign the document with Mobile ID'),
					'autoOpen'=>false,
					'modal'=>true,
					'width'=>400,
					'height'=>280
				),
				$this->mobileModalOptions
			)
		));
			echo $this->_mobileSign;
		$this->endWidget('zii.widgets.jui.CJuiDialog');
	}

	/**
	 * Render buttons that open modals
	 */
	protected function renderModalButtons()
	{
		// set card sign button
		$cardSignBtn=CHtml::link($this->getCardSignLabel(), '#', array(
			'class'=>!$this->enableImages ? $this->buttonCssClass : null,
			'onclick'=>'$("#cardSignModal").dialog("open"); return false;'
		));

		// set mobile sign button
		$mobileSignBtn=CHtml::link($this->getMobileSignLabel(), '#', array(
			'class'=>!$this->enableImages ? $this->buttonCssClass : null,
			'onclick'=>'$("#mobileSignModal").dialog("open"); return false;'
		));

		// display by template
		echo strtr($this->modalButtonsTemplate, array(
			'{cardSignBtn}'=>$cardSignBtn,
			'{mobileSignBtn}'=>$mobileSignBtn
		));
	}

	/**
	 * Render content by CJuiTabs
	 */
	protected function renderTabs()
	{
		$this->widget('CTabView',array(
			'activeTab'=>'tab1',
			'tabs'=>array(
				'tab1'=>array(
					'title'=>Yii::t('XDigiDocWidget.digidoc', 'Sign the document with ID Card'),
					'content'=>$this->_cardSign
				),
				'tab2'=>array(
					'title'=>Yii::t('XDigiDocWidget.digidoc', 'Sign the document with Mobile ID'),
					'content'=>$this->_mobileSign
				),
			),
			'htmlOptions'=>array_merge(
				array(
					'style'=>'width: 500px',
				),
				$this->tabsHtmlOptions
			)
		));
	}

	/**
	 * Render content by template
	 */
	protected function renderTemplate()
	{
		echo strtr($this->template, array(
			'{cardTitle}'=>Yii::t('XDigiDocWidget.digidoc', 'Sign the document with ID Card'),
			'{mobileTitle}'=>Yii::t('XDigiDocWidget.digidoc', 'Sign the document with Mobile ID'),
			'{cardSign}'=>$this->_cardSign,
			'{mobileSign}'=>$this->_mobileSign
		));
	}

	/**
	 * @return string card sign text or image tag
	 */
	protected function getCardSignLabel()
	{
		return $this->enableImages ?
			CHtml::image($this->_assets.'/img/id-card-logo.gif', Yii::t('XDigiDocWidget.digidoc', 'Sign the document with ID Card')) :
			Yii::t('XDigiDocWidget.digidoc', 'Sign the document with ID Card');
	}

	/**
	 * @return string mobile sign text or image tag
	 */
	protected function getMobileSignLabel()
	{
		return $this->enableImages ?
			CHtml::image($this->_assets.'/img/mobile-id-logo.gif', Yii::t('XDigiDocWidget.digidoc', 'Sign the document with Mobile ID')) :
			Yii::t('XDigiDocWidget.digidoc', 'Sign the document with Mobile ID');
	}
}