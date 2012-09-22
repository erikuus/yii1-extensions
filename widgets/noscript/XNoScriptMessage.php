<?php
/**
 * XNoScriptMessage class file.
 *
 * @author Stefan Volkmar <volkmar_yii@email.de>
 * @version 1.0
 * @license BSD
 */

/**
 *
 * This widget create a message if javascript isn't enabled in the browser
 *
 * @author Stefan Volkmar <volkmar_yii@email.de>
 */

class XNoScriptMessage extends CWidget
{
	/**
	 * @var mixed the CSS file used for the widget.
	 * If false, the default CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 */
	public $cssFile=false;

	/**
	 * Initializes the widget.
	 * This method registers all needed client scripts
	 */
	public function init()
	{
		$baseUrl = CHtml::asset(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');
		$url = ($this->cssFile!==false)
			? $this->cssFile
			: $baseUrl.'/XNoScriptMessage.css';

		$jsCode = "$(document).ready(function(){\n";
		$jsCode .= "$(\"noscript\").hide();\n";
		$jsCode .= "})\n";

		Yii::app()->getClientScript()
			->registerCssFile($url)
			->registerCoreScript('jquery')
			->registerScript(__CLASS__,$jsCode,CClientScript::POS_HEAD);

		echo "<noscript>\n";
		echo "<div id=\"js-info\">\n";
		echo "<h2 style=\"color:red\">\n";
	}

	/**
	 * Renders the close tag of the element.
	 */
	public function run()
	{
		echo "</h2>\n";
		echo "</div>\n";
		echo "</noscript>\n";
	}
}