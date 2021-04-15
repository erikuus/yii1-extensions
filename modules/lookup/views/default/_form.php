<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'lookup-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p><?php echo Yii::t('LookupModule.ui', 'Fields with {mark} are required',
	array('{mark}'=>'<span class="required">*</span>')); ?>
</p>

	<?php // echo $form->errorSummary($model); ?>

	<?php echo $form->hiddenField($model,'type'); ?>

	<div class="simple">
		<?php echo $form->labelEx($model,'name_et'); ?>
		<?php echo $form->textField($model,'name_et',array('size'=>60,'maxlength'=>256)); ?>
		<?php echo $form->error($model,'name_et'); ?>
	</div>

	<div class="simple">
		<?php echo $form->labelEx($model,'name_en'); ?>
		<?php echo $form->textField($model,'name_en',array('size'=>60,'maxlength'=>256)); ?>
		<?php echo $form->error($model,'name_en'); ?>
	</div>

	<div class="action">
		<?php echo CHtml::submitButton($model->isNewRecord ? Yii::t('LookupModule.ui', 'Create') : Yii::t('LookupModule.ui','Save'), array('class'=>$this->module->primaryButtonCssClass)); ?>
		<?php echo CHtml::link(Yii::t('LookupModule.ui', 'Cancel'), $this->getReturnUrl() ? $this->getReturnUrl() : array('admin'), array('class'=>$this->module->secondaryButtonCssClass)); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->