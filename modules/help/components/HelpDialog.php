<?php
/**
 * HelpDialog class file.
 *
 * Call this widget as follows:
 *
 * $this->widget('ext.modules.help.components.HelpDialog');
 *
 * Then you can insert link that opens help text in dialog
 *
 * echo CHtml::link(
 *     Yii::t('ui','Explain something'),
 *     array('/help/default/view','code'=>'explain_something'),
 *     array('class'=>'openhelp')
 * );
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class HelpDialog extends CWidget
{
	/**
	 * @var array options for CJuiDialog
	 */
	public $options=array();

	/**
	 * Initializes the widget.
	 */
	public function init()
	{
		$script =
<<<SCRIPT
	jQuery('a.openhelp').click(function (event) {
		event.preventDefault();
		var targetUrl = $(this).attr('href');
		$('#{$this->id}').dialog({
			open : function(){
				$('#{$this->id}').text('');
				$('#{$this->id}').load(targetUrl);
			}
	});
	jQuery('#{$this->id}').dialog('open');
	});
SCRIPT;

		Yii::app()->clientScript->registerScript(__CLASS__, $script, CClientScript::POS_READY);
	}

	/**
	 * Renders the widget.
	 */
	public function run()
	{
		$this->beginWidget('zii.widgets.jui.CJuiDialog', array(
			'id'=>$this->id,
			'options'=>array_merge(
				array(
					'title'=>null,
					'width'=>750,
					'height'=>450,
					'autoOpen'=>false,
					'modal'=>true,
				),
				$this->options
			)
		));
		$this->endWidget('zii.widgets.jui.CJuiDialog');
	}
}