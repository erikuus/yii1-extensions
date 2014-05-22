<?php $form=$this->beginWidget('CActiveForm', array(
	'action'=>Yii::app()->createUrl($this->route),
	'method'=>'get',
)); ?>

	<?php echo $form->DropDownList($model,'menu_id', PageMenu::model()->activeItemOptions, array(
		'submit'=>Yii::app()->createUrl($this->route),
		'prompt'=>Yii::t('PageModule.ui', '-All Articles-'),
	));?>
	<?php echo $form->textField($model,'title'); ?>
	<?php echo CHtml::submitButton(Yii::t('PageModule.ui', 'Search')); ?>

<?php $this->endWidget(); ?>