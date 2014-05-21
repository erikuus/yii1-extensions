<?php $form=$this->beginWidget('CActiveForm', array(
	'action'=>Yii::app()->createUrl($this->route),
	'method'=>'get',
)); ?>

	<?php echo $form->DropDownList($model,'menu_id', PageMenu::model()->activeItemOptions, array(
		'submit'=>Yii::app()->createUrl($this->route),
		'prompt'=>Yii::t('PageModule.ui', '-All Articles-'),
		'style'=>'width: 200px'
	));?>
	<?php echo $form->textField($model,'title',array('style'=>'width: 200px;')); ?>
	<?php echo CHtml::submitButton(Yii::t('PageModule.ui', 'Search'), array('class'=>$this->module->secondaryButtonCssClass)); ?>

<?php $this->endWidget(); ?>