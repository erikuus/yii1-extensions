<?php
/**
 * XContextMenu extends CWidget and implements a base class for jQuery contextMenu.
 *
 * The jQuery contextMenu Plugin was designed for web applications in need of menus on a possibly large amount of objects.
 * Unlike other implementations this contextMenu treats the menu as the primary object. That means, that a single
 * menu is defined that can be used by multiple objects.
 *
 * The following examples show how to use XBookReader widget.
 *
 * Example 1: Context menu with a trigger tag
 *
 * $this->beginWidget('ext.widgets.cmenu.XContextMenu', array(
 *     'triggerTagName'=>'span',
 *     'options'=>array(
 *         'callback'=>"js:function(key, opt) {
 *             window.console && console.log(key);
 *         }",
 *         'items'=>array(
 *             'hide'=>array(
 *                 'name'=>'Hide',
 *                 'callback'=>"js:function(key, opt) {
 *                     alert('Clicked on ' + key);
 *                 }"
 *             ),
 *             'show'=>array(
 *                 'name'=>'Show',
 *                 'callback'=>"js:function(key, opt) {
 *                     alert('Clicked on ' + key);
 *                 }"
 *             ),
 *             'sep'=>'---------',
 *             'quit'=>array(
 *                 'name'=>'Cancel',
 *             ),
 *         )
 *     )
 * )); ?>
 *     Right click me
 * $this->endWidget(); ?>
 *
 * Example 2: Context menu with selector and icons
 *
 * $this->widget('ext.widgets.cmenu.XContextMenu', array(
 *     'options'=>array(
 *         'selector'=>'#change-log img',
 *         'callback'=>"js:function(key, opt) {
 *             window.console && console.log(key);
 *         }",
 *         'items'=>array(
 *             'hide'=>array(
 *                 'name'=>'Hide',
 *                 'icon'=>'fas fa-eye-slash',
 *                 'callback'=>"js:function(key, opt) {
 *                     alert('Clicked on ' + key);
 *                 }",
 *                 'disabled'=>"js:function() {
 *                     return false;
 *                 }"
 *             ),
 *             'show'=>array(
 *                 'name'=>'Show',
 *                 'icon'=>'fas fa-eye-slash',
 *                 'callback'=>"js:function(key, opt) {
 *                     alert('Clicked on ' + key);
 *                 }",
 *                 'disabled'=>"js:function() {
 *                     return true;
 *                 }"
 *             ),
 *             'sep'=>'---------',
 *             'quit'=>array(
 *                 'name'=>'Cancel',
 *             ),
 *         )
 *     )
 * )); ?>
 *
 * Note that you need fontawesome kit js in your HTML page in
 * order to use icons in context menu!
 *
 * @link https://swisnl.github.io/jQuery-contextMenu
 * @link https://github.com/swisnl/jQuery-contextMenu
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XContextMenu extends CWidget
{
	/**
	 * @var mixed the CSS file used for the widget
	 * Defaults to null, meaning using the default CSS file included together with the widget.
	 * If false, no CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 */
	public $cssFile;
	/**
	 * @var string the HTML tag name for the element that triggers context menu
	 */
	public $triggerTagName;
	/**
	 * @var array HTML attributes for the trigger element
	 */
	public $htmlOptions=array();
	/**
	 * @var array the initial JavaScript options that should be passed to the IIPMooViewer plugin.
	 * Possible options include the following:
	 * selector - The jQuery selector matching the elements to trigger on. This option is mandatory. Example:
	 *     array(
	 *         "selector"=>"span.context-menu"
	 *     )
	 * items - Object with items to be listed in contextMenu. Example:
	 *     array(
	 *         "items"=>array(
	 *             array(
	 *                 "name"=>"copy",
	 *                 "icon"=>"fas fa-eye-slash",
	 *                 "callback"=>"js:function(key, opt) {
	 *                     alert("Clicked on " + key);
	 *                 }",
	 *                 "disabled"=>"js:function() {
	 *                     return false;
	 *                 }"
	 *             )
	 *         )
	 *     )
	 * appendTo - The selector string or DOMElement the generated menu is to be appended to. Example:
	 *     array(
	 *         "appendTo"=>"div#context-menus-container"
	 *     )
	 * trigger - Specifies what event triggers the contextmenu [default:"right"]. Example:
	 *     array(
	 *         "trigger"=>"left" // options: right|left|hover|touchstart|none
	 *     )
	 * delay - The time in milliseconds to wait before showing the menu. Only applies to trigger "hover". Example:
	 *     array(
	 *         "trigger"=>"hover",
	 *         "delay"=>500
	 *     )
	 * animation - Animation properties take effect on showing and hiding the menu. Example:
	 *     array(
	 *         "animation"=>array(
	 *             array(
	 *                 "duration"=>250,
	 *                 "show"=>"fadeIn",
	 *                 "hide"=>"fadeOut",
	 *             )
	 *         )
	 *     )
	 * events - The show and hide events are triggered before the menu is shown or hidden. The event handlers may
	 *     return false to prevent the show or hide process. Options: preShow|show|hide|activated. Example:
	 *     array(
	 *         "events"=>array(
	 *             "show"=>"js:function(options){
	 *                  this.addClass('currently-showing-menu');
	 *              }",
	 *             "hide"=>"js:function(options){
	 *                  if( confirm('Hide menu with selector ' + options.selector + '?') === true) {
	 *                      return true;
	 *                  } else {
	 *                      return false;
	 *                  }
	 *              }"
	 *         )
	 *     )
	 * position - Callback to override the position of the context menu. The first argument is the $menu jQuery
	 *     object, which is the menu element. The second and third arguments are x and y coordinates provided by the show
	 *     event. The x and y may either be integers denoting the offset from the top left corner, undefined, or the
	 *     string "maintain". If the string "maintain" is provided, the current position of the $menu must be used. If
	 *     the coordinates are undefined, appropriate coordinates must be determined. An example of how this can be
	 *     achieved is provided with determinePosition. Example:
	 *     array(
	 *         "position"=>"js:function(opt, x, y) {
	 *             opt.$menu.css({top: 123, left: 123});
	 *         }"
	 *     )
	 * determinePosition - Determine the position of the menu in respect to the given trigger object, this function is
	 *     called when there is no x and y set on the position call.. Example:
	 *     array(
	 *         "determinePosition"=>"js:function($menu) {
	 *             $menu.css('display', 'block')
	 *                 .position({ my: 'center top', at: 'center bottom', of: this, offset: '0 5'})
	 *                 .css('display', 'none');
	 *         }"
	 *     )
	 * callback - Specifies the default callback to be used in case an item does not expose its own callback.
	 *     The default callback behaves just like item.callback. Example:
	 *     array(
	 *         "callback"=>"js:function(itemKey, opt) {
	 *             alert('Clicked on ' + itemKey + ' on element ' + opt.$trigger.attr('id'));
	 *         }"
	 *     )
	 * build - The callback is executed with two arguments given: the jQuery reference to the triggering element and
	 *     the original contextmenu event. It is executed without context (so this won't refer to anything useful).
	 *     If the build callback is found at registration, the menu is not built right away. The menu creation is
	 *     delayed to the point where the menu is actually called to show. Dynamic menus don't stay in the DOM.
	 *     After a menu created with build is hidden, its DOM-footprint is destroyed. With build, only the options
	 *     selector and trigger may be specified in the options object. All other options need to be returned from
	 *     the build callback. Example:
	 *     array(
	 *         "build"=>"js:function($triggerElement, e){
	 *             return {
	 *                 callback: function(){},
	 *                 items: {
	 *                     menuItem: {name: 'My on demand menu item'}
	 *                 }
	 *             };
	 *         }"
	 *     )
	 * itemClickEvent - Allows the selection of the click event instead of the mouseup event to handle the user mouse
	 *     interaction with the contexMenu. The default event is mouseup. Set the option to "click" to change to the
	 *     click event. This option is global: the first contexMenu registered sets it. To change it afterwards all
	 *     the contextMenu have to be unregistered with $.contextMenu( 'destroy' ). Example:
	 *     array(
	 *         "itemClickEvent"=>"click"
	 *     )
	 * className - Additional classNames to add to the menu element. Example:
	 *     array(
	 *         "className"=>"contextmenu-custom contextmenu-custom__highlight"
	 *     )
	 * classNames - The base class names of the contextmenu elements. This can be used to change the class
	 *     names of some classes that might conflict with frameworks like Bootstrap. Example:
	 *     array(
	 *         "classNames"=>array(
	 *             "hover"=>"hover", // Item hover
	 *             "disabled"=>"disabled", // Item disabled
	 *             "visible"=>"visible", // Item visible
	 *             "notSelectable"=>"not-selectable", // Item not selectable
	 *             "icon"=>"context-menu-icon", // Base icon class
	 *             "iconEdit"=>"context-menu-icon-edit",
	 *             "iconCut"=>"context-menu-icon-cut",
	 *             "iconCopy"=>"context-menu-icon-copy",
	 *             "iconPaste"=>"context-menu-icon-paste",
	 *             "iconAdd"=>"context-menu-icon-add",
	 *             "iconQuit"=>"context-menu-icon-quit",
	 *             "iconLoadingClass"=>"context-menu-icon-loading"
	 * 			)
	 *     )
	 * zIndex - The offset to add to the calculated zIndex of the trigger element. Set to 0 to prevent zIndex
	 *     manipulation. Can be a function that returns an int to calculate the zIndex on build. Example:
	 *     array(
	 *         "zIndex"=>"js:function($trigger, opt) {
	 *             return 120;
	 *         }"
	 *     )
	 * hideOnSecondTrigger - Whether a second trigger should close the menu [default: false].
	 * selectableSubMenu - Whether menu items containing submenus should be clickable [default: false].
	 * autoHide - Whether the menu must be hidden when the mouse pointer is moved out [default: false].
	 * reposition - Whether a menu should be repositioned (true) or rebuilt (false) if a second trigger event
	 *     is performed on the same element (or its children) while the menu is still visible [default: true].
	 *
	 */
	public $options=array();

	/**
	 * Initializes the widget.
	 * Registers client scripts and opens trigger tag
	 */
	public function init()
	{
		if(isset($this->htmlOptions['id']))
			$this->id=$this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$this->id;

		$this->registerClientScript();
		$this->registerClientScriptFiles();

		if($this->triggerTagName)
			echo CHtml::openTag($this->triggerTagName, $this->htmlOptions)."\n";
	}

	/**
	 * Renders content and closes tag
	 */
	public function run()
	{
		if($this->triggerTagName)
		{
			$this->renderContent();
			echo CHtml::closeTag($this->triggerTagName);
		}
	}

	/**
	 * Renders the body part of the widget.
	 */
	protected function renderContent()
	{
	}

	/**
	 * Register necessary inline client scripts.
	 */
	protected function registerClientScript()
	{
		// prepare options
		if(!isset($this->options['selector']))
			$this->options['selector']='#'.$this->htmlOptions['id'];

		$options=CJavaScript::encode($this->options);

		// register inline javascript
		Yii::app()->getClientScript()->registerScript(__CLASS__, "
			$.contextMenu({$options});
		", CClientScript::POS_READY);
	}

	/**
	 * Publish and register necessary client script files.
	 */
	protected function registerClientScriptFiles()
	{
		// publish
		$assets=Yii::app()->assetManager->publish(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');

		// register scripts
		$cs=Yii::app()->clientScript;

		// register core script
		$cs->scriptMap['jquery.js']=$assets.'/jquery-1.8.2.min.js';
		$cs->registerCoreScript('jquery');

		// register widget css file
		if($this->cssFile===null)
		{
			$assets=Yii::app()->assetManager->publish(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');
			$cs->registerCssFile($assets.'/jquery.contextMenu.min.css');
		}
		elseif($this->cssFile!==false)
			$cs->registerCssFile($this->cssFile);

		// register widget js files
		$cs->registerScriptFile($assets.'/jquery.contextMenu.min.js', CClientScript::POS_HEAD);
		$cs->registerScriptFile($assets.'/jquery.ui.position.min.js', CClientScript::POS_HEAD);
	}
}