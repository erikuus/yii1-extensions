<?php
/**
 * XPasswordStrength displays password field with password strength indicator.
 *
 * XPasswordStrength encapsulates the {@link http://mypocket-technologies.com/jquery/password_strength/} plugin.
 *
 * To use this widget, you may insert the following code in a view:
 * <pre>
 * $this->widget('ext.widgets.password.XPasswordStrength', array(
 *     'model'=>$model,
 *     'attribute'=>'password',
 * ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

class XPasswordStrength extends CInputWidget
{
	/**
	 * @var mixed the CSS file used for the widget. Defaults to null, meaning
	 * using the default CSS file included together with the widget.
	 * If false, no CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 */
	public $cssFile;
	/**
	 * @var array jquery plugin options:
	 * minLength:    	6;
	 * shortPassText:    "Too short";
	 * badPassText:      "Weak";
	 * goodPassText:     "Good";
	 * strongPassText:   "Strong";
	 * shortPassCss:     "shortPass",
	 * badPassCss:       "badPass",
	 * goodPassCss:      "goodPass",
	 * strongPassCss:    "strongPass",
	 * baseStyleCss:     "testPass",
	 */
	public $options=array();

    /**
     * Run this widget.
     * This method registers necessary CSS and JS files and renders the needed JS and HTML code.
     */
	public function run()
	{
		list($name,$id)=$this->resolveNameID();

		if(isset($this->htmlOptions['id']))
			$id=$this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;

		if(isset($this->htmlOptions['name']))
			$name=$this->htmlOptions['name'];

		if($this->hasModel())
			echo CHtml::activePasswordField($this->model,$this->attribute,$this->htmlOptions);
		else
			echo CHtml::passwordField($name,$this->value,$this->htmlOptions);

		$this->registerClientScript();
		$this->registerClientScriptFiles();
	}

	/**
	 * Register necessary inline client scripts.
	 */
	protected function registerClientScript()
	{
		$id=$this->htmlOptions['id'];
		$cs=Yii::app()->clientScript;

		// prepare options
		$options = CJavaScript::encode($this->options);

		// register inline script
		$script="jQuery('#{$id}').passStrength({$options})\n";

		$cs->registerScript(__CLASS__ . '#' . $id, $script, CClientScript::POS_READY);
	}

	/**
	 * Publish and register necessary client script files.
	 */
	protected function registerClientScriptFiles()
	{
		$cs=Yii::app()->clientScript;
		$assets=Yii::app()->assetManager->publish(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');

		// register css file
		if($this->cssFile===null)
			$cs->registerCssFile($assets.'/password_strength.css');
		else if($this->cssFile!==false)
			$cs->registerCssFile($this->cssFile);

		// register js files
		$cs->registerCoreScript('jquery');
		$cs->registerScriptFile($assets. '/jquery.password_strength.js');
	}
}