<?php Yii::app()->clientScript->registerScript('toggle', "
	var typeContentVal=".PageMenu::TYPE_CONTENT.";
	var typeUrlVal=".PageMenu::TYPE_URL.";
	var typeCssId='".CHtml::activeId($model,'type')."';
	var typeVal=$('#'+typeCssId+' option:selected').val();
	$('#content-container').css('display', typeVal==typeContentVal ? 'block':'none');
	$('#url-container').css('display', typeVal==typeUrlVal ? 'block':'none');
	$('#'+typeCssId).change(function(){
		var typeVal=$('#'+typeCssId+' option:selected').val();
		$('#content-container').css('display', typeVal==typeContentVal ? 'block':'none');
		$('#url-container').css('display', typeVal==typeUrlVal ? 'block':'none');
	});
");
?>

<div class="form" style="width: auto">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'menu-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p><?php echo Yii::t('PageModule.ui', 'Fields with {mark} are required',
	array('{mark}'=>'<span class="required">*</span>')); ?></p>

	<?php echo $form->errorSummary($model); ?>

	<?php $this->beginContent('/decorators/formRow')?>
		<?php echo $form->labelEx($model, 'title'); ?>
		<?php echo $form->textField($model, 'title', array('style'=>'width: 300px')); ?>
		<?php echo $form->error($model, 'title'); ?>
	<?php $this->endContent()?>

	<?php $this->beginContent('/decorators/formRow')?>
		<?php echo $form->labelEx($model, 'type'); ?>
		<?php echo $form->dropDownList($model, 'type', $model->typeOptions, array('prompt'=>'','style'=>'width:300px')); ?>
		<?php echo $form->error($model, 'type'); ?>
	<?php $this->endContent()?>

	<div id="content-container" style="display: none">
	<?php $this->beginContent('/decorators/formRow')?>
		<?php echo $form->labelEx($model,'content'); ?>
		<?php $this->widget('ext.widgets.xheditor.XHeditor',array(
			'model'=>$model,
			'modelAttribute'=>'content',
			'config'=>array(
				'id'=>CHtml::activeId($model,'content'),
				'loadCSS'=>$this->module->editorSideContentCssFile ? $this->module->editorSideContentCssFile : null,
				'tools'=>$this->module->editorMenuTools,
				'width'=>'600px',
				'height'=>'300px',
				'upImgUrl'=>$this->createUrl('request/uploadFile'),
				'upImgExt'=>$this->module->editorUploadAllowedImageExtensions,
				'upLinkUrl'=>$this->createUrl('request/uploadFile'),
				'upLinkExt'=>$this->module->editorUploadAllowedLinkExtensions,
			)
		));?>
		<?php echo $form->error($model,'content'); ?>
	<?php $this->endContent()?>
	</div>

	<div id="url-container" style="display: none">
	<?php $this->beginContent('/decorators/formRow')?>
		<?php echo CHtml::activeLabel($model,'url',array('required'=>true)); ?>
		<?php echo $form->textField($model,'url', array('style'=>'width: 600px')); ?>
		<?php echo $form->error($model,'url'); ?>
	<?php $this->endContent()?>
	</div>

	<?php $this->beginContent('/decorators/formButtonsRow')?>
		<?php echo CHtml::submitButton($model->isNewRecord ? Yii::t('PageModule.ui', 'Create') : Yii::t('PageModule.ui','Save'), array('class'=>$this->module->primaryButtonCssClass)); ?>
		<?php echo CHtml::link(Yii::t('PageModule.ui', 'Cancel'), $this->getReturnUrl() ? $this->getReturnUrl() : array('admin'), array('class'=>$this->module->secondaryButtonCssClass)); ?>
	<?php $this->endContent()?>

<?php $this->endWidget(); ?>

</div><!-- form -->