<?php
/**
 * XVauHelpdesk class file
 *
 * Widget to implement a VAU Helpdesk service
 *
 * Example of usage:
 * <pre>
 *     $this->widget('ext.widgets.vau.XVauHelpdesk', array(
 *         'label'=>Yii::t('ui','FAQ and Feedback'),
 *         'visible'=>Yii::app()->params['vauHelpdesk'],
 *         'lang'=>Yii::app()->language,
 *     ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0.0
 */
class XVauHelpdesk extends CWidget
{
	/**
	 * @var boolean whether the portlet is visible. Defaults to true.
	 */
	public $visible=true;
	/**
	 * @var string name of the helpdesk link.
	 */
	public $label;
	/**
	 * @var string the title attribute of helpdesk link.
	 */
	public $title;
	/**
	 * @var array additional HTML attributes of helpdesk link.
	 */
	public $htmlOptions=array();
	/**
	 * @var string the name of language (et|en) for VAU helpdesk.
	 */
	public $lang;
	/**
	 * @var string the dev url (if set used instead _vauUrl)
	 */
	public $devUrl;

	private $_cssClass='vauHelpdesk';
	private $_vauUrl='http://www.ra.ee/vau/index.php/helpdesk/message/feedback?';

	public function run()
	{
		if(!$this->visible)
			return;

		$this->registerClientScript();

		$urlParams=array(
			'language'=>$this->lang,
			'url'=>$this->controller->createAbsoluteUrl('',$_GET),
			'dialog'=>1
		);

		$url=$this->devUrl ? $this->devUrl : $this->_vauUrl;
		$url.=http_build_query($urlParams);

		if(!isset($this->htmlOptions['title']))
			$this->htmlOptions['title']=$this->title;

		if(!isset($this->htmlOptions['class']))
			$this->htmlOptions['class']=$this->_cssClass;
		else
			$this->htmlOptions['class'].=' '.$this->_cssClass;

		echo CHtml::link($this->label, $url, $this->htmlOptions);
	}

	/**
	 * Publish and register necessary client scripts.
	 */
	protected function registerClientScript()
	{
		$script =
<<<SCRIPT
	jQuery(document).delegate(".{$this->_cssClass}","click", function(e){
		e.preventDefault();
		window.open(this.href,"","top=100,left=100,width=800,height=600,resizable=yes,location=no,menubar=no,scrollbars=yes,status=no,toolbar=no,fullscreen=no,dependent=no");
	});
SCRIPT;

		Yii::app()->getClientScript()->registerScript(__CLASS__, $script, CClientScript::POS_READY);
	}
}