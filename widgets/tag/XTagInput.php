<?php
/**
 * XTagInput displays simple and configurable tag editing widget with autocomplete support.
 *
 * XTagInput encapsulates the {@link http://aehlke.github.com/tag-it/} plugin.
 *
 * Note that this widget can be used together with ETaggableBehavior
 * {@link http://code.google.com/p/yiiext/}
 *
 * To use this widget, you may insert the following code in a view:
 * <pre>
 * $this->widget('ext.widgets.tag.XTagInput', array(
 *     'name'=>'tags',
 *     'value'=>$model->tags->toString(),
 *     'options'=>array(
 *         'availableTags'=>$model->getAllTags()
 *     )
 * ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

Yii::import('zii.widgets.jui.CJuiInputWidget');

class XTagInput extends CJuiInputWidget
{
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
			echo CHtml::activeTextField($this->model,$this->attribute,$this->htmlOptions);
		else
			echo CHtml::textField($name,$this->value,$this->htmlOptions);

		$options=CJavaScript::encode($this->options);

		$js="jQuery('#{$id}').tagit($options);";

        $assets=CHtml::asset(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');
        $cs=Yii::app()->getClientScript();
        $cs->registerCssFile($assets.'/jquery.tagit.css');
		$cs->registerScriptFile($assets.'/tag-it.js', CClientScript::POS_END); // Position is important here!
		$cs->registerScript(__CLASS__.'#'.$id, $js);
	}
}