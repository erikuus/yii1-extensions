<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'user-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p><?php echo Yii::t('UserModule.ui', 'Fields with {mark} are required',
	array('{mark}'=>'<span class="required">*</span>')); ?></p>

	<?php echo $form->errorSummary($model); ?>

	<?php if($model->isNewRecord):?>
	<div class="simple">
		<?php echo $form->labelEx($model,'username'); ?>
		<?php echo $form->textField($model,'username',array('size'=>32,'maxlength'=>64)); ?>
		<?php echo $form->error($model,'username'); ?>
	</div>

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
	<?php endif; ?>

	<div class="simple">
		<?php echo $form->labelEx($model,'firstname'); ?>
		<?php echo $form->textField($model,'firstname',array('size'=>32,'maxlength'=>64)); ?>
		<?php echo $form->error($model,'firstname'); ?>
	</div>

	<div class="simple">
		<?php echo $form->labelEx($model,'lastname'); ?>
		<?php echo $form->textField($model,'lastname',array('size'=>32,'maxlength'=>64)); ?>
		<?php echo $form->error($model,'lastname'); ?>
	</div>

	<?php if(User::model()->groupOptions!==array()): ?>
	<div class="simple">
		<?php echo $form->labelEx($model,'usergroup'); ?>
		<?php echo $form->DropDownList($model,'usergroup', User::model()->groupOptions);?>
		<?php echo $form->error($model,'usergroup'); ?>
	</div>
	<?php endif; ?>

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