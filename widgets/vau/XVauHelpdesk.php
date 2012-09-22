<?php
/**
 * XVauHelpdesk class file
 *
 * Widget to implement a VAU Helpdesk service
 *
 * Example of usage:
 * <pre>
 *     $this->widget('ext.widgets.vau.XVauHelpdesk', array(
 *         'title'=>Yii::t('ui','FAQ and Feedback'),
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
	private $cssClass='vauHelpdesk';

	/**
	 * @var boolean whether the portlet is visible. Defaults to true.
	 */
	public $visible = true;

	/**
	 * @var string name of the helpdesk link. If not set, default icon is used.
	 */
	public $label;

	/**
	 * @var string the title attribute of helpdesk link.
	 */
	public $title;

	/**
	 * @var string the name of language (et|en) for VAU helpdesk.
	 */
	public $lang;

	/**
	 * @var integer the id of the VAU FAQ subject. If not set, helpdesk link will open Send Message page.
	 */
	public $id;

	public function run()
	{
		if(!$this->visible)
			return;

		$baseUrl=$this->registerClientScript();

		$text=$this->label ?
			CHtml::encode($this->label) :
			CHtml::image($baseUrl.'/helpdesk.gif',$this->title,array('title'=>$this->title,'style'=>'margin-left: 3px'));

		$params=array(
			'page'=>'HelpFAQPage',
			'SendMessage'=>1,
			'_lang'=>$this->lang,
			'Url'=>$this->controller->createAbsoluteUrl('',$_GET),
			'host'=>Yii::app()->request->hostInfo
		);

		$url='http://www.ra.ee/vau/redirect.php?'.http_build_query($params);

		echo CHtml::link($text,$url,array('class'=>$this->cssClass,'title'=>$this->title));
	}

	/**
	 * Publish and register necessary client scripts.
	 */
	protected function registerClientScript()
	{
		$script =
<<<SCRIPT
	jQuery(".{$this->cssClass}").live("click", function(e){
		e.preventDefault();
		window.open(this.href,"","top=100,left=100,width=800,height=600,resizable=yes,location=no,menubar=no,scrollbars=yes,status=no,toolbar=no,fullscreen=no,dependent=no");
	});
SCRIPT;

		Yii::app()->getClientScript()->registerScript(__CLASS__, $script, CClientScript::POS_READY);

		$assets = dirname(__FILE__).'/assets';
		$baseUrl = Yii::app()->assetManager->publish($assets);
		return $baseUrl;
	}
}