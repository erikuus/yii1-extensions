<?php
/**
 * XDynamicForm class file.
 *
 * XDynamicForm enables to use checkbox and radio button lists so that
 * when a checkbox or radio button is checked/unchecked some content
 * next to checkbox or radio button is shown/hidden
 *
 * For example, we may have model Person where there is 'gender' property
 * and gender options defined as follows:
 *
 * <pre>
 * class Person extends CActiveRecord
 * {
 *     const GENDER_MALE='m';
 *     const GENDER_FEMALE='f';
 *
 *     public function getGenderOptions()
 *     {
 *         return array(
 *             self::GENDER_MALE=>'Male',
 *             self::GENDER_FEMALE=>'Female',
 *         );
 *     }
 * }
 * <pre>
 *
 * Now we can use XDynamicForm as follows:
 *
 * <pre>
 * <?php $form=$this->beginWidget('ext.widgets.form.XDynamicForm', array('id'=>'dynamic-form')); ?>
 *
 *     <?php $checkBox=$form->explodeCheckBoxList($model, 'gender', $model->genderOptions); ?>
 *
 *     <?php $form->beginDynamicArea($checkBox[Person::GENDER_MALE]); ?>
 *         This content is displayed only when 'male' checkbox is checked
 *     <?php $form->endDynamicArea(); ?>
 *
 *     <?php $form->beginDynamicArea($checkBox[Person::GENDER_FEMALE]); ?>
 *         This content is displayed only when 'female' checkbox is checked
 *     <?php $form->endDynamicArea(); ?>
 *
 * <?php $this->endWidget(); ?>
 * <pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XDynamicForm extends CActiveForm
{
	/**
	 * @var string CSS class for container that holds checkbox/radiobutton
	 * and the sibling tag that is shown only when checkbox/radiobutton is selected.
	 * Defaults to 'dynamic-container'
	 */
	public $containerCssClass='dynamic-container';
	/**
	 * @var string CSS class for the tag that is shown only when sibling checkbox/radiobutton is selected.
	 * Defaults to 'dynamic-content'
	 */
	public $contentCssClass='dynamic-content';
	/**
	 * @var boolean whether to enable radiobutton to toggle dynamic area
	 * Defaults to true
	 */
	public $enableRadioToggle=true;
	/**
	 * @var boolean whether to enable checkbox to toggle dynamic area
	 * Defaults to true
	 */
	public $enableChecboxToggle=true;

	/**
	 * Initializes the widget.
	 */
	public function init()
	{
		if($this->enableRadioToggle)
			$this->registerRadioClientScript();

		if($this->enableChecboxToggle)
			$this->registerCheckboxClientScript();

		parent::init();
	}

	/**
	 * Explodes radioButtonList into array
	 * enabling to render buttons separately ($radio[0], $radio[1], etc)
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $data value-label pairs used to generate the radio button list
	 * @param string $class css class nam.
	 * @return array of radio buttons
	 */
	public function explodeRadioButtonList($model, $attribute, $data, $class=null)
	{
		$radioButtons=explode('|',$this->radioButtonList($model, $attribute, $data, array(
			'template'=>'{input}{label}',
			'separator'=>'|',
			'class'=>$class
		)));

		$dataKeys=array_keys($data);
		$indexRadioButtons=array();
		foreach ($radioButtons as $i=>$radioButton) {
			$index=$dataKeys[$i];
			$indexRadioButtons[$index]=$radioButton;
		}
		return $indexRadioButtons;
	}

	/**
	 * Explodes checkBoxList into array
	 * enabling to render boxes separately ($box[0], $box[1], etc)
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $data value-label pairs used to generate the check box list
	 * @param string $class css class name
	 * @return array of check boxes
	 */
	public function explodeCheckBoxList($model, $attribute, $data, $class=null)
	{
		$checkBoxes=explode('|',$this->checkBoxList($model, $attribute, $data, array(
			'template'=>'{input}{label}',
			'separator'=>'|',
			'class'=>$class
		)));

		$dataKeys=array_keys($data);
		$indexCheckBoxes=array();
		foreach ($checkBoxes as $i=>$checkBox) {
			$index=$dataKeys[$i];
			$indexCheckBoxes[$index]=$checkBox;
		}
		return $indexCheckBoxes;
	}

	/**
	 * Generates open HTML elements for dynamic area.
	 * @param string $input radiobutton or checkbox HTML
	 * @param array $containerOptions the container tag attributes.
	 * @param array $contentOptions the content tag attributes.
	 */
	public function beginDynamicArea($input, $containerOptions=array(), $contentOptions=array())
	{
		if(isset($containerOptions['class']))
			$containerOptions['class'].=' '.$this->containerCssClass;
		else
			$containerOptions = array_merge($containerOptions, array('class'=>$this->containerCssClass));

		if(isset($contentOptions['class']))
			$contentOptions['class'].=' '.$this->contentCssClass;
		else
			$contentOptions = array_merge($contentOptions, array('class'=>$this->contentCssClass));

		echo CHtml::openTag('div', $containerOptions);
			echo $input;
			echo CHtml::openTag('div', $contentOptions);
	}

	/**
	 * Renders close HTML elements for dynamic area.
	 */
	public function endDynamicArea()
	{
			echo '</div>';
		echo '</div>';
	}

	/**
	 * Generates static area for input element with no toggle content.
	 * @param string $input radiobutton or checkbox HTML
	 * @param array $htmlOptions the tag attributes.
	 */
	public function staticArea($input, $htmlOptions=array())
	{
		if(isset($htmlOptions['class']))
			$htmlOptions['class'].=' '.$this->containerCssClass;
		else
			$htmlOptions = array_merge($htmlOptions, array('class'=>$this->containerCssClass));

		echo CHtml::tag('div', $htmlOptions, $input);
	}

	/**
	 * Register client script for radio
	 */
	protected function registerRadioClientScript()
	{
		$script =
<<<SCRIPT
	$('#{$this->id} .{$this->containerCssClass} :radio:not(:checked)').siblings('.{$this->contentCssClass}').hide();
	$('#{$this->id} .{$this->containerCssClass} :radio').live('click',function() {
		$('.{$this->contentCssClass}', $(this).parents('div:first')).css('display', this.checked ? 'block':'none');
		$('#{$this->id} .{$this->containerCssClass} :radio:not(:checked)').siblings('.{$this->contentCssClass}').hide();
	});
SCRIPT;
		Yii::app()->clientScript->registerScript(__CLASS__.'#radio#'.$this->id, $script, CClientScript::POS_READY);
	}

	/**
	 * Register client script for checkbox
	 */
	protected function registerCheckboxClientScript()
	{
		$script =
<<<SCRIPT
	$('#{$this->id} .{$this->containerCssClass} :checkbox:not(:checked)').siblings('.{$this->contentCssClass}').hide();
	$('#{$this->id} .{$this->containerCssClass} :checkbox').live('click',function() {
		$('.{$this->contentCssClass}', $(this).parents('div:first')).css('display', this.checked ? 'block':'none');
	});
SCRIPT;
		Yii::app()->clientScript->registerScript(__CLASS__.'#checkbox#'.$this->id, $script, CClientScript::POS_READY);
	}
}