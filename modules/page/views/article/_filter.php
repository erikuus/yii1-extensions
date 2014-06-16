<?php $form=$this->beginWidget('CActiveForm', array(
	'action'=>Yii::app()->createUrl($this->route),
	'method'=>'get',
)); ?>

	<?php echo $form->DropDownList($model,'menu_id', PageMenu::model()->activeItemOptions, array(
		'submit'=>Yii::app()->createUrl($this->route),
		'prompt'=>Yii::t('PageModule.ui', '-All Articles-'),
		'style'=>'width:250px'
	));?>

<?php $this->endWidget(); ?>