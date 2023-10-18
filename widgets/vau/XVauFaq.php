<?php
/**
 * XVauFaq class file
 *
 * Widget to implement a VAU FAQ service
 *
 * Example of usage:
 * <pre>
 *     $this->widget('ext.widgets.vau.XVauFaq', array(
 *         'label'=>Yii::t('ui','FAQ'),
 *         'visible'=>Yii::app()->params['vauFaq'],
 *         'lang'=>Yii::app()->language,
 *     ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0.0
 */
class XVauFaq extends CWidget
{
	/**
	 * @var boolean whether the portlet is visible. Defaults to true.
	 */
	public $visible=true;
	/**
	 * @var string name of the faq link.
	 */
	public $label;
	/**
	 * @var array additional HTML attributes of faq link.
	 */
	public $htmlOptions=array();
	/**
	 * @var array list of url params for link. Possible option names include the following:
	 * dialog=1: display all subjects in dialog layout
	 * subjectIds=1,3,10: dispaly subjects with given id only
	 * btnAll=1: display "Show all" button
	 * btnAsk=1: display "Ask Question" button
	 * color=0000B4: customize color
	 */
	public $urlParams=array();
	/**
	 * @var string the name of language (et|en) for VAU fag.
	 */
	public $lang;
	/**
	 * @var string the dev url (if set used instead _vauUrl)
	 */
	public $devUrl;

	private $_cssClass='vauFaq';
	private $_vauUrl='https://www.ra.ee/vau/index.php/et/helpdesk/faq/index?';

	public function run()
	{
		if(!$this->visible)
			return;

		$this->registerClientScript();

		$url=$this->devUrl ? $this->devUrl : $this->_vauUrl;

		if(!isset($this->urlParams['language']))
			$this->urlParams['language']=$this->lang;

		$url.=http_build_query($this->urlParams);

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