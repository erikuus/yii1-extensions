<?php
/**
 * XFoundation class file.
 *
 * Inserts client scripts needed for selected components of Foundation 5 CSS Framework.
 *
 * Following components are supported:
 * - Grid
 * - Block Grid
 * - Visibility
 * - Off Canvas (optional)
 *
 * This selection is based on author's preference (1) to use components that help to
 * build responsive layout and (2) to avoid using typography and UI components.
 *
 * The following shows how to use Foundation component:
 *
 * 1. Configure component
 *
 * <pre>
 * 'preload'=>array(
 *     'foundation'
 * ),
 * 'components'=>array(
 *     'foundation'=>array(
 *          'class'=>'ext.components.foundation.XFoundation',
 *          'maxWidth'=>'75em'
 *     ),
 * )
 * </pre>
 *
 * 2. In layout or in view files you can now use
 *
 * 2.1 Grid
 *
 * <div class="row">
 *     <div class="large-4 medium-4 small-12 columns">
 *         ...
 *     </div>
 *     <div class="large-4 medium-4 small-12 columns">
 *         ...
 *     </div>
 *     <div class="large-4 medium-4 small-12 columns">
 *         ...
 *     </div>
 * </div>
 *
 * 2.2 Block Grid
 *
 * <div class="row">
 *     <div class="large-12 columns">
 *         <ul class="small-block-grid-2 medium-block-grid-3 large-block-grid-4">
 *             <li><img src="img.jpg"></li>
 *             <li><img src="img.jpg"></li>
 *             <li><img src="img.jpg"></li>
 *             <li><img src="img.jpg"></li>
 *             <li><img src="img.jpg"></li>
 *             <li><img src="img.jpg"></li>
 *         </ul>
 *     </div>
 * </div>
 *
 * 2.3 Visibility
 *
 * <p class="show-for-small-only">This text is shown only on a small screen.</p>
 * <p class="show-for-medium-up">This text is shown on medium screens and up.</p>
 * <p class="show-for-medium-only">This text is shown only on a medium screen.</p>
 * <p class="show-for-large-up">This text is shown on large screens and up.</p>
 * <p class="show-for-large-only">This text is shown only on a large screen.</p>
 * <p class="show-for-xlarge-up">This text is shown on xlarge screens and up.</p>
 * <p class="show-for-xlarge-only">This text is shown only on an xlarge screen.</p>
 * <p class="show-for-xxlarge-up">This text is shown on xxlarge screens and up.</p>
 * <p class="show-for-landscape">You are in landscape orientation.</p>
 * <p class="show-for-portrait">You are in portrait orientation.</p>
 * <p class="show-for-touch">You are on a touch-enabled device.</p>
 * <p class="hide-for-touch">You are not on a touch-enabled device.</p>
 *
 * 2.3 Off Canvas
 *
 * <div class="show-for-small">
 *     <a class="left-off-canvas-toggle menu-icon">
 *         <span>Menu</span>
 *     </a>
 * </div>
 *
 * <aside class="left-off-canvas-menu">
 * ...
 * </aside>
 *
 * For more info refer to foundation docs http://foundation.zurb.com/docs/
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XFoundation extends CApplicationComponent
{
	/**
	 * @var string grid max width. If not set, default max-width defined in foundation.css is used.
	 */
	public $maxWidth;

	/**
	 * @var boolean whether to include javascripts needed for off canvas component. Defaults to true.
	 */
	public $offCanvas=true;

	protected $_assetsUrl;

	/**
	 * Initializes the component.
	 */
	public function init()
	{
		$cs=Yii::app()->clientScript;
		$cs->registerCssFile($this->getAssetsUrl().'/css/foundation.css');
		$cs->registerCssFile($this->getAssetsUrl().'/css/normalize.css');

		// override grid max width
		if($this->maxWidth)
			$cs->registerCss(__CLASS__, ".row {max-width: $this->maxWidth;}", "screen", CClientScript::POS_HEAD);

		// register js only if off canvas is needed
		if($this->offCanvas)
			$this->registerJs();
	}

	/**
	 * Registers the javascripts
	 */
	public function registerJs()
	{
		$cs=Yii::app()->clientScript;
		$cs->registerCoreScript('jquery');
		$cs->registerScriptFile($this->getAssetsUrl().'/js/modernizr.js', CClientScript::POS_HEAD);
		$cs->registerScriptFile($this->getAssetsUrl().'/js/foundation.min.js', CClientScript::POS_END);
		$cs->registerScript(__CLASS__, '$(document).foundation();', CClientScript::POS_END);
	}

	/**
	 * Publishes assets and returns the URL to the published assets folder.
	 * @return string the URL
	 */
	protected function getAssetsUrl()
	{
		if($this->_assetsUrl==null)
		{
			$assetsPath=dirname(__FILE__).DIRECTORY_SEPARATOR.'assets';
			$this->_assetsUrl=Yii::app()->assetManager->publish($assetsPath, false, -1, YII_DEBUG);
		}
		return $this->_assetsUrl;
	}
}
