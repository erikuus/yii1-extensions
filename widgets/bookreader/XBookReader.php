<?php
/**
 * XBookReader extends CWidget and implements a base class for the Internet Archive BookReader.
 *
 * The Internet Archive BookReader is used to view books from the Internet Archive online and can also be used to view other books.
 *
 * IMPORTANT!!! When using this widget start html page as follows:
 * <!doctype html>
 *
 * For users who have disabled javascript you should add:
 * <noscript>
 *     The BookReader requires JavaScript to be enabled.
 * </noscript>
 *
 * The following example shows how to use XBookReader widget:
 * <pre>
 * $this->widget('ext.widgets.bookreader.XBookReader', array(
 *    'options'=>array(
 *        'enableMobileNav'=>true,
 *        'enablePluginUrl'=>true,
 *        'data'=>array(
 *            array(
 *                array(
 *                    'width'=>1920,
 *                    'height'=>1200,
 *                    'uri'=>'//www.plato.com/00001.jpg'
 *                )
 *            ),
 *            array(
 *                array(
 *                    'width'=>1920,
 *                    'height'=>1200,
 *                    'uri'=>'//www.plato.com/00002.jpg'
 *                ),
 *                array(
 *                    'width'=>1920,
 *                    'height'=>1200,
 *                    'uri'=>'//www.plato.com/00003.jpg'
 *                )
 *            ),
 *            array(
 *                array(
 *                    'width'=>1920,
 *                    'height'=>1200,
 *                    'uri'=>'//www.plato.com/00004.jpg'
 *                ),
 *                array(
 *                    'width'=>1920,
 *                    'height'=>1200,
 *                    'uri'=>'//www.plato.com/00005.jpg'
 *                )
 *            )
 *        ),
 *        'thumbnail'=>'//www.plato.com/00001.jpg',
 *        'bookTitle'=>'The works of Plato',
 *        'metadata'=>array(
 *            array(
 *                'label'=>'Title',
 *                'value'=>'The works of Plato',
 *            ),
 *            array(
 *                'label'=>'Author',
 *                'value'=>'Plato',
 *            ),
 *            array(
 *                'label'=>'Info',
 *                'value'=>'A new and literal version, chiefly from the text of Stallbaum',
 *            )
 *        )
 *    )
 * ));
 * </pre>
 *
 * @link https://openlibrary.org/dev/docs/bookreader
 * @link https://github.com/internetarchive/bookreader
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XBookReader extends CWidget
{
	/**
	 * @var array the initial JavaScript options that should be passed to the BookReader
	 * Possible options include the following:
	 * <pre>
	 * <b>data</b>: The list of images to be displayed in bookreader.
	 *        This path does not need to be in the web server root directory.
	 *        On Windows as on other systems this should be a UNIX style path such as "/path/to/image.tif"
	 * <b>getNumLeafs</b>: function that returns total number of leafs, e.g. "js:function() {return 15;}"
	 * <b>getPageWidth</b>: function that returns the width of a given page, e.g. "js:function(index) {return 1000;}"
	 * <b>getPageHeight</b>: function that returns the height of a given page, e.g. "js:function(index) {return 1000;}"
	 * <b>getPageNum</b>: function that returns page number, e.g. "js:function(index) {return index+1;}"
	 * <b>getPageSide</b>: function that returns which side a given page should be displayed on. For example:
	 *     "js:function(index) {
	 *         if(0==(index & 0x1)) {
	 *             return 'R';
	 *         } else {
	 *             return 'L';
	 *         }
	 *     }"
	 * <b>getPageURI</b>: function that loads images from server. For example:
	 *     "js:function(index, reduce, rotate) {
	 *         var leafStr = '000';
	 *         var imgStr = (index+1).toString();
	 *         var re = new RegExp("0{"+imgStr.length+"}$");
	 *         var url = 'http://archive.org/download/BookReader/img/page'+leafStr.replace(re, imgStr) + '.jpg';
	 *         return url;
	 *     }",
	 *     Note that reduce and rotate are ignored in this simple example, but we could reduce and load
	 *     images from a different directory or pass the information to an image server.
	 * <b>getSpreadIndices</b>: function that returns the left and right indices for the user-visible
	 *     spread that contains the given index. The return values may be null if there is no facing
	 *     page or the index is invalid. For example:
	 *     "js:function(pindex) {
	 *         var spreadIndices = [null, null];
	 *         if ('rl' == this.pageProgression) {
	 *             if (this.getPageSide(pindex) == 'R') {
	 *                 spreadIndices[1] = pindex;
	 *                 spreadIndices[0] = pindex + 1;
	 *             } else {
	 *                 spreadIndices[0] = pindex;
	 *                 spreadIndices[1] = pindex - 1;
	 *             }
	 *         } else {
	 *             if (this.getPageSide(pindex) == 'L') {
	 *                 spreadIndices[0] = pindex;
	 *                 spreadIndices[1] = pindex + 1;
	 *             } else {
	 *                 spreadIndices[1] = pindex;
	 *                 spreadIndices[0] = pindex - 1;
	 *             }
	 *         }
	 *         return spreadIndices;
	 *     }"
	 * <b>getEmbedCode</b>: function that returns embed code for share dialog. For example:
	 *     "js:function(frameWidth, frameHeight, viewParams) {
	 *         return "Embed code not supported for this book.";
	 *     }"
	 * <b>bookTitle</b>: book title displayed as text in the top left corner, if bookUrl is not set
	 * <b>bookUrl</b>: url of the link displayed in the top left corner
	 * <b>bookUrlText</b>: label of the link displayed in the top left corner
	 * <b>bookUrlTitle</b>: title of the link displayed in the top left corner
	 * <b>thumbnail</b>: thumbnail is optional, but it is used in the info dialog
	 * <b>metadata</b>: metadata is optional, but it is used in the info dialog. For example:
	 *     metadata: [
	 *         {label: "Title", value: "Demo"},
	 *         {label: "Author", value: "Erik Uus"},
	 *         {label: "Demo Info", value: "This demo shows how to use BookReader."},
	 *     ],
	 *
	 * <b>enableMobileNav</b>: whether the mobile drawer is displayed [default: true]
	 * <b>mobileNavTitle</b>: the mobile drawer title
	 * <b>imagesBaseURL</b>: path to UI images [default: "/assets/images/"]
	 * <b>ui</b>: user interface mode [options: full|embed; default: "full"]
	 * </pre>
	 *
	 */
	public $options=array();
	/**
	 * @var array the extra JavaScript options that should be passed to the BookReader
	 * For example array('mobileNavFullscreenOnly'=>true)
	 * Defaults to array()
	 */
	public $extraOptions=array();
	/**
	 * @var array HTML attributes for the viewer container tag
	 * Defaults to array()
	 */
	public $htmlOptions=array();
	/**
	 * @var boolean whether to enable archive analytics plugin
	 * Defaults to false
	 */
	public $enablePluginArchiveAnalytics=false;
	/**
	 * @var boolean whether to enable autoplay plugin
	 * Defaults to false
	 */
	public $enablePluginAutoplay=false;
	/**
	 * @var boolean whether to enable chapter plugin
	 * Defaults to false
	 */
	public $enablePluginChapter=false;
	/**
	 * @var boolean whether to enable iframe plugin
	 * Defaults to false
	 */
	public $enablePluginIframe=false;
	/**
	 * @var boolean whether to enable menu toggle plugin
	 * Defaults to false
	 */
	public $enablePluginMenuToggle=false;
	/**
	 * @var boolean whether to enable mobile nav plugin
	 * Defaults to false
	 */
	public $enablePluginMobileNav=false;
	/**
	 * @var boolean whether to enable print plugin
	 * Defaults to false
	 */
	public $enablePluginPrint=false;
	/**
	 * @var boolean whether to enable resume plugin
	 * Defaults to false
	 */
	public $enablePluginResume=false;
	/**
	 * @var boolean whether to enable search plugin
	 * Defaults to false
	 */
	public $enablePluginSearch=false;
	/**
	 * @var boolean whether to enable themes plugin
	 * Defaults to false
	 */
	public $enablePluginThemes=false;
	/**
	 * @var boolean whether to enable tts plugin
	 * Defaults to false
	 */
	public $enablePluginTts=false;
	/**
	 * @var boolean whether to enable url plugin
	 * Defaults to false
	 */
	public $enablePluginUrl=false;
	/**
	 * @var boolean whether to enable vendor fullscreen plugin
	 * Defaults to false
	 */
	public $enablePluginVendorFullscreen=false;
	/**
	 * @var string language. Options [en | et]
	 * Defaults to 'en'
	 */
	public $lang='en';

	/**
	 * Renders the open tag of the viewer.
	 * This method also registers the necessary javascript code.
	 */
	public function init()
	{
		$baseUrl=$this->registerClientScript();

		if(!isset($this->options['imagesBaseURL']))
			$this->options['imagesBaseURL']=$baseUrl.'/images/';

		$id=$this->getId();
		if(isset($this->htmlOptions['id']))
			$id = $this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;

		if(!isset($this->options['ui']))
			$this->options['ui']='full';

		$this->options['el']='js:selector';
		$options=CJavaScript::encode($this->options);
		$extraOptions=$this->extraOptions!==array() ? CJavaScript::encode($this->extraOptions) : '';

		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id, "
			instantiateBookReader('#$id', $extraOptions) {
				selector = selector || '#BookReader';
				extraOptions = extraOptions || {};
				var options = $options;
				$.extend(options, extraOptions);
				var br = new BookReader(options);
				br.init();
			}
		", CClientScript::POS_END);

		echo CHtml::openTag('div',$this->htmlOptions)."\n";
	}

	/**
	 * Renders the close tag of the dialog.
	 */
	public function run()
	{
		echo CHtml::closeTag('div');

		if($this->lang!='en')
			$this->translate();
	}

	/**
	 * Register client script to translate.
	 */
	protected function translate()
	{
		Yii::app()->getClientScript()->registerScript(__CLASS__, '
			$(".BRfloatHeadTitle").text("'.Yii::t('XBookReader.bookreader', 'About this book').'");
			$(".info").text("'.Yii::t('XBookReader.bookreader', 'Info').'");
			$(".share").text("'.Yii::t('XBookReader.bookreader', 'Share').'");
			$(".info").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'About this book').'");
			$(".share").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Share this book').'");
			$(".BRnavCntl").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Show/hide nav').'");
			$(".book_left").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Flip left').'");
			$(".book_right").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Flip right').'");
			$(".onepg").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Ühe lehekülje vaade').'");
			$(".twopg").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Kahe lehekülje vaade').'");
			$(".thumb").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Pisipildid').'");
			$(".zoom_out").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Vähenda').'");
			$(".zoom_in").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Suurenda').'");
			$(".full").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Toggle fullscreen').'");
			$(".read").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Loe valjusti').'");
		', CClientScript::POS_READY);
	}

	/**
	 * Publish and register necessary client script files.
	 */
	protected function registerClientScript()
	{
		// publish
		$assets = dirname(__FILE__).'/assets';
		$baseUrl = Yii::app()->assetManager->publish($assets);

		$cs=Yii::app()->clientScript;

		// meta tags
		$cs->registerMetaTag('width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no', 'viewport');
		$cs->registerMetaTag('yes', 'apple-mobile-web-app-capable');

		// js dependencies
		$cs->registerScriptFile($baseUrl.'/jquery-1.10.1.js', CClientScript::POS_HEAD);
		$cs->registerScriptFile($baseUrl.'/jquery-ui-1.12.0.min.js', CClientScript::POS_HEAD);
		$cs->registerScriptFile($baseUrl.'/jquery.browser.min.js', CClientScript::POS_HEAD);
		$cs->registerScriptFile($baseUrl.'/dragscrollable-br.js', CClientScript::POS_HEAD);
		$cs->registerScriptFile($baseUrl.'/jquery.colorbox-min.js', CClientScript::POS_HEAD);
		$cs->registerScriptFile($baseUrl.'/jquery.bt.min.js', CClientScript::POS_HEAD);

		// mmenu library
		$cs->registerCssFile($baseUrl.'/mmenu/dist/css/jquery.mmenu.css');
		$cs->registerCssFile($baseUrl.'/mmenu/dist/addons/navbars/jquery.mmenu.navbars.css');
		$cs->registerScriptFile($baseUrl.'/mmenu/dist/js/jquery.mmenu.min.js', CClientScript::POS_HEAD);
		$cs->registerScriptFile($baseUrl.'/mmenu/dist/addons/navbars/jquery.mmenu.navbars.min.js', CClientScript::POS_HEAD);

		// bookreader main plugin
		$cs->registerCssFile($baseUrl.'/BookReader.css');
		$cs->registerScriptFile($baseUrl.'/BookReader.js', CClientScript::POS_HEAD);

		// additional plugins
		if($this->enablePluginArchiveAnalytics)
			$cs->registerScriptFile($baseUrl.'/plugins/plugin.archive_analytics.js', CClientScript::POS_HEAD);

		if($this->enablePluginAutoplay)
			$cs->registerScriptFile($baseUrl.'/plugins/plugin.autoplay.js', CClientScript::POS_HEAD);

		if($this->enablePluginChapter)
			$cs->registerScriptFile($baseUrl.'/plugins/plugin.chapters.js', CClientScript::POS_HEAD);

		if($this->enablePluginIframe)
			$cs->registerScriptFile($baseUrl.'/plugins/plugin.iframe.js', CClientScript::POS_HEAD);

		if($this->enablePluginMenuToggle)
			$cs->registerScriptFile($baseUrl.'/plugins/plugin.menu_toggle.js', CClientScript::POS_HEAD);

		if($this->enablePluginMobileNav)
			$cs->registerScriptFile($baseUrl.'/plugins/plugin.mobile_nav.js', CClientScript::POS_HEAD);

		if($this->enablePluginPrint)
			$cs->registerScriptFile($baseUrl.'/plugins/plugin.print.js', CClientScript::POS_HEAD);

		if($this->enablePluginResume)
			$cs->registerScriptFile($baseUrl.'/plugins/plugin.resume.js', CClientScript::POS_HEAD);

		if($this->enablePluginSearch)
			$cs->registerScriptFile($baseUrl.'/plugins/plugin.search.js', CClientScript::POS_HEAD);

		if($this->enablePluginThemes)
			$cs->registerScriptFile($baseUrl.'/plugins/plugin.themes.js', CClientScript::POS_HEAD);

		if($this->enablePluginTts)
			$cs->registerScriptFile($baseUrl.'/plugins/plugin.tts.js', CClientScript::POS_HEAD);

		if($this->enablePluginUrl)
			$cs->registerScriptFile($baseUrl.'/plugins/plugin.url.js', CClientScript::POS_HEAD);

		if($this->enablePluginVendorFullscreen)
			$cs->registerScriptFile($baseUrl.'/plugins/plugin.vendor-fullscreen.js', CClientScript::POS_HEAD);

		return $baseUrl;
	}
}