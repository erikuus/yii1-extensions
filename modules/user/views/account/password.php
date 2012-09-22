<?php
$this->pageTitle=Yii::app()->name . ' - ' . Yii::t('UserModule.ui', 'Change Password');
$this->breadcrumbs=array(
	Yii::t('UserModule.ui', 'My Account')=>array('index'),
	Yii::t('UserModule.ui', 'Change Password'),
);
?>

<h2><?php echo Yii::t('UserModule.ui', 'Change User "{user}" Password',array('{user}'=>$model->username)); ?></h2>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'user-form',
	'enableAjaxValidation'=>false,
	'action'=>$this->createReturnStackUrl('changePassword',array('id'=>$model->id)),

)); ?>

	<p><?php echo Yii::t('UserModule.ui', 'Fields with {mark} are required',
	array('{mark}'=>'<span class="required">*</span>')); ?></p>

	<?php echo $form->errorSummary($model); ?>

	<div class="simple">
		<?php echo $form->labelEx($model,'password'); ?>
		<?php echo $form->passwordField($model,'password',array('size'=>32,'maxlength'=>64)); ?>
		<?php echo $form->error($model,'password'); ?>
	</div>

	<div class="simple">
		<?php echo $form->labelEx($model,'repeatPassword'); ?>
		<?php echo $form->passwordField($model,'repeatPassword',array('size'=>32,'maxlength'=>64)); ?>
		<?php echo $form->error($model,'repeatPassword'); ?>
	</div>

	<div class="action">
		<?php $this->widget('zii.widgets.jui.CJuiButton', array(
			'buttonType'=>'submit',
			'name'=>'btnSubmit',
			'value'=>'Submit',
			'caption'=>$model->isNewRecord ? Yii::t('UserModule.ui', 'Create') : Yii::t('UserModule.ui','Save'),
		));  ?>
		<?php $this->widget('zii.widgets.jui.CJuiButton', array(
			'buttonType'=>'link',
			'name'=>'btnCancel',
			'value'=>'Cancel',
			'caption'=>Yii::t('UserModule.ui', 'Cancel'),
			'url'=>array('index'),
		)); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->