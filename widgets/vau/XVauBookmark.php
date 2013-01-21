<?php
/**
 * XVauBookmark class file
 *
 * Widget to implement a VAU Bookmark service
 *
 * Example of usage:
 * <pre>
 *     $this->widget('ext.widgets.vau.XVauBookmark', array(
 *         'addTitle'=>Yii::t('ui','Bookmark this page to VAU linkbook'),
 *         'listTitle'=>Yii::t('ui','Open VAU linkbook'),
 *         'visible'=>Yii::app()->params['vauBookmark'],
 *         'lang'=>Yii::app()->language,
 *     ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0.0
 */
class XVauBookmark extends CWidget
{
	private $_cssClass='vauBookmark';
	private $_vauUrl='http://www.ra.ee/vau/index.php';

	/**
	 * @var boolean whether the portlet is visible. Defaults to true.
	 */
	public $visible = true;

	/**
	 * @var boolean whether the 'Add bookmark' link is visible. Defaults to true.
	 */
	public $addTag = true;

	/**
	 * @var boolean whether the 'My bookmarks' link is visible. Defaults to true.
	 */
	public $listTag = true;

	/**
	 * @var string the title attribute of the 'Add bookmark' link.
	 */
	public $addTitle;

	/**
	 * @var string the title attribute of the 'My bookmarks' link.
	 */
	public $listTitle;

	/**
	 * @var string name of the of the 'Add bookmark' link. If not set, icon is used.
	 */
	public $addLabel;

	/**
	 * @var string name of the 'My Bookmarks' link. If not set, icon is used.
	 */
	public $listLabel;

	/**
	 * @var string url for saving as bookmark. If not set, url of the current page is used.
	 */
	public $addUrl;

	/**
	 * @var string the name of language (et|en) for VAU linkbook.
	 */
	public $lang;

	/**
	 * @var string the name of GET parameter that should be removed from url.
	 * For example, set it to '_xr' if you want to remove default stack parameter of XReturnableBehavior.
	 */
	public $removeParam;

	public function run()
	{
		if(!$this->visible)
			return;

		$baseUrl=$this->registerClientScript();

		if($this->addTag)
		{
			$text=$this->getText($this->addLabel,$baseUrl.'/bookmark.gif',$this->addTitle);
			echo CHtml::link($text,$this->getOpenAddBookmarkUrl(),array('class'=>$this->_cssClass,'title'=>$this->addTitle));
		}
		if($this->listTag)
		{
			$text=$this->getText($this->listLabel,$baseUrl.'/bookmarkList.gif',$this->listTitle);
			echo CHtml::link($text,$this->getOpenListBookmarkUrl(),array('class'=>$this->_cssClass,'title'=>$this->listTitle));
		}
	}

	/**
	 * @param string label
	 * @param string image url
	 * @param string image title
	 * @return string text for link
	 */
	protected function getText($label, $image, $title)
	{
		return $label ? CHtml::encode($label) : CHtml::image($image,$title,array('title'=>$title,'style'=>'margin-left: 3px'));
	}

	/**
	 * @return string url to list bookmark
	 */
	protected function getOpenListBookmarkUrl()
	{
		return "{$this->_vauUrl}/{$this->lang}/bookmark/linkDialog/";
	}

	/**
	 * @return string url to add bookmark
	 */
	protected function getOpenAddBookmarkUrl()
	{
		$params=array(
			'url'=>$this->getBookmarkUrl(),
			'app'=>Yii::app()->name
		);
		return "{$this->_vauUrl}/{$this->lang}/bookmark/linkDialog/create?".http_build_query($params);
	}

	/**
	 * @return string url to be bookmarked
	 */
	protected function getBookmarkUrl()
	{
		if($this->addUrl)
		{
			return $this->addUrl;
		}
		else
		{
			$params=$_GET;
			if($this->removeParam)
				unset($params[$this->removeParam]);
			return $this->controller->createAbsoluteUrl('',$params);
		}
	}

	/**
	 * Publish and register necessary client scripts.
	 */
	protected function registerClientScript()
	{
		$script =
<<<SCRIPT
	jQuery(".{$this->_cssClass}").live("click", function(e){
		e.preventDefault();
		window.open(this.href,"","top=0,left=0,width=1024,height=600,resizable=yes,location=no,menubar=no,scrollbars=yes,status=no,toolbar=no,fullscreen=no,dependent=no");
	});
SCRIPT;

		Yii::app()->getClientScript()->registerScript(__CLASS__, $script, CClientScript::POS_READY);

		$assets = dirname(__FILE__).'/assets';
		$baseUrl = Yii::app()->assetManager->publish($assets);
		return $baseUrl;
	}
}