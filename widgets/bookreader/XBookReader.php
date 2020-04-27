<?php
/**
 * XBookReader extends CWidget and implements a base class for the Internet Archive BookReader.
 *
 * The Internet Archive BookReader is used to view books from the Internet Archive online and can also be used to view other books.
 *
 * The following example shows how to use XBookReader widget:
 *
 * <!DOCTYPE html>
 * <html>
 * <head>
 *     <title></title>
 *     <style>
 *         html, body {width: 100%; height: 100%; margin: 0; padding: 0}
 *         #BookReader {width: 100%; height: 100%; background-color: #fff}
 *     </style>
 * </head>
 * <body>
 * $this->widget('ext.widgets.bookreader.XBookReader', array(
 *    'enableMobileNav'=>true,
 *    'enablePluginUrl'=>true,
 * 	  'htmlOptions'=>array(
 *        'id'=>'BookReader'
 * 	  ),
 *    'options'=>array(
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
 *        'thumbnail'=>'//www.plato.com/00000.jpg',
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
 * </body>
 * </html>
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
	 * data: The list of images to be displayed in bookreader. For example:
	 * array(
	 *    array(
	 *       array(
	 *            'width'=>1920,
	 *            'height'=>1200,
	 *            'uri'=>'//www.plato.com/00001.jpg',
	 *            'pageNum'=> 1' // optional
	 *       )
	 *    ),
	 *    array(
	 *       array(
	 *            'width'=>1920,
	 *            'height'=>1200,
	 *            'uri'=>'//www.plato.com/00002.jpg'
	 *       ),
	 *       array(
	 *            'width'=>1920,
	 *            'height'=>1200,
	 *            'uri'=>'//www.plato.com/00003.jpg'
	 *       )
	 *    )
	 * )
	 * bookTitle: book title displayed as text in the top left corner
	 * enableBookTitleLink whether to enable book title link [default: true]
	 * bookUrl: url of the link displayed in the top left corner
	 * bookUrlText: label of the link displayed in the top left corner
	 * bookUrlTitle: title of the link displayed in the top left corner
	 * thumbnail: thumbnail is optional, but it is used in the info dialog
	 * metadata: metadata is optional, but it is used in the info dialog. For example:
	 *     array(
	 *         array("label"=>"Title", "value"=>"Demo"),
	 *         array("label"=>"Author", "value"=>"Erik Uus"),
	 *         array("label"=>"Demo Info", "value"=>"This demo shows how to use BookReader.")
	 *     ),
	 *
	 * enableMobileNav: whether the mobile drawer is displayed [default: true]
	 * mobileNavTitle: the mobile drawer title
	 * imagesBaseURL: path to UI images [default: "/assets/images/"]
	 * ui: user interface mode [options: "full"|"embed"; default: "full"]
	 * defaults: view modes that must be prefixed with 'mode/' [options: "1up"|"2up"|"thumb"; default: "2up"]
	 * padding: the padding in 1up mode [default: 10]
	 * onePage: the object to hold parameters related to 1up mode. For example:
	 *     array(
	 *         "autofit"=>"auto" // [options: "width"|"height"|"auto"|"none"]
	 *     )
	 * twoPage: the object to hold parameters related to 2up mode. For example:
	 *     array(
	 *         'coverInternalPadding'=>0,
	 *         'coverExternalPadding'=>0,
	 *         'bookSpineDivWidth'=>64,
	 *         'autofit'=>'auto'
	 *     )
	 * uiAutoHide: whether nav/toolbar will autohide [default: false]
	 * thumbRowBuffer: the number of rows to pre-cache out a view [default: 2]
	 * thumbColumns: the number of tumbnail columns [default: 6]
	 * thumbMaxLoading: the number of thumbnails to load at once [default: 4]
	 * thumbPadding: the spacing between thumbnails [default: 10]
	 * thumbPadding: the speed for flip animation [options: integer|"fast"|"slow"; default: "fast"]
	 * showToolbar: whether to show toolbar [default: true]
	 * showNavbar: whether to show navbar [default: true]
	 * pageProgression: the page progression direction [options: "lr" | "rl"; default: "lr"]
	 * protected: whether to block download [default: false]
	 * reductionFactors:
	 *     array(
	 *         array("reduce"=>0.5, "autofit"=>null},
	 *         array("reduce"=>1, "autofit"=>null},
	 *         array("reduce"=>2, "autofit"=>null},
	 *         array("reduce"=>3, "autofit"=>null},
	 *         array("reduce"=>4, "autofit"=>null},
	 *         array("reduce"=>6, "autofit"=>null}
	 *     )
	 * getNumLeafs: function that returns total number of leafs, e.g. "js:function() {return 15;}"
	 * getPageWidth: function that returns the width of a given page, e.g. "js:function(index) {return 1000;}"
	 * getPageHeight: function that returns the height of a given page, e.g. "js:function(index) {return 1000;}"
	 * getPageNum: function that returns page number, e.g. "js:function(index) {return index+1;}"
	 * getPageSide: function that returns which side a given page should be displayed on. For example:
	 *     "js:function(index) {
	 *         if(0==(index & 0x1)) {
	 *             return 'R';
	 *         } else {
	 *             return 'L';
	 *         }
	 *     }"
	 * getPageURI: function that loads images from server. For example:
	 *     "js:function(index, reduce, rotate) {
	 *         var leafStr = '000';
	 *         var imgStr = (index+1).toString();
	 *         var re = new RegExp("0{"+imgStr.length+"}$");
	 *         var url = 'http://archive.org/download/BookReader/img/page'+leafStr.replace(re, imgStr) + '.jpg';
	 *         return url;
	 *     }",
	 *     Note that reduce and rotate are ignored in this simple example, but we could reduce and load
	 *     images from a different directory or pass the information to an image server.
	 * getSpreadIndices: function that returns the left and right indices for the user-visible
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
	 * getEmbedCode: function that returns embed code for share dialog. For example:
	 *     "js:function(frameWidth, frameHeight, viewParams) {
	 *         return "Embed code not supported for this book.";
	 *     }"
	 *
	 * </pre>
	 */
	public $options=array();
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

		if(!isset($this->options['defaults']))
			$this->options['defaults']='mode/1up';

		$this->options['el']='js:selector';
		$options=CJavaScript::encode($this->options);

		Yii::app()->getClientScript()->registerScript(__CLASS__.'Init'.$id, "
			function instantiateBookReader(selector) {
				var options = $options;
				var br = new BookReader(options);
				br.init();
			}
			instantiateBookReader('#$id');
		", CClientScript::POS_END);

		if($this->lang!='en')
			$this->translate();

		echo CHtml::openTag('div', $this->htmlOptions)."\n";
	}

	/**
	 * Renders the close tag of the dialog.
	 */
	public function run()
	{
		echo CHtml::closeTag('div');
	}

	/**
	 * Register client script to translate.
	 */
	protected function translate()
	{
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#translate', '
			$(".BRfloatHeadTitle").text("'.Yii::t('XBookReader.bookreader', 'About this book').'");
			$(".info").text("'.Yii::t('XBookReader.bookreader', 'Info').'");
			$(".share").text("'.Yii::t('XBookReader.bookreader', 'Share').'");
			$(".info").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'About this book').'");
			$(".share").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Share this book').'");
			$(".BRnavCntl").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Show/hide nav').'");
			$(".book_left").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Flip left').'");
			$(".book_right").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Flip right').'");
			$(".onepg").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'One-page view').'");
			$(".twopg").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Two-page view').'");
			$(".thumb").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Grid view').'");
			$(".zoom_out").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Zoom out').'");
			$(".zoom_in").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Zoom in').'");
			$(".full").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Toggle fullscreen').'");
			$(".read").attr("bt-xtitle","'.Yii::t('XBookReader.bookreader', 'Read this book aloud').'");
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