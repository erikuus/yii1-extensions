<?php
$cs=Yii::app()->clientScript;

if($this->module->formCssFile===null)
	$cs->registerCssFile($this->getAsset('/css/form.css'));
else if($this->module->formCssFile!==false)
	$cs->registerCssFile($this->module->formCssFile);

$cs->registerScript('toggle', "
	var content=".PageMenu::TYPE_CONTENT.";
	var hidden=".PageMenu::TYPE_HIDDEN_CONTENT.";
	var url=".PageMenu::TYPE_URL.";
	var typeId='".CHtml::activeId($model,'type')."';
	var type=$('#'+typeId+' option:selected').val();
	$('#content-container').css('display', type==content || type==hidden ? 'block':'none');
	$('#url-container').css('display', type==url ? 'block':'none');
	$('#'+typeId).change(function(){
		var type=$('#'+typeId+' option:selected').val();
		$('#content-container').css('display', type==content || type==hidden ? 'block':'none');
		$('#url-container').css('display', type==url ? 'block':'none');
	});
");
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'menu-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p><?php echo Yii::t('PageModule.ui', 'Fields with {mark} are required',
	array('{mark}'=>'<span class="required">*</span>')); ?></p>

	<?php echo $form->errorSummary($model); ?>

	<div class="simple">
		<?php echo $form->labelEx($model, 'title'); ?>
		<?php echo $form->textField($model, 'title', array('style'=>'width: 300px')); ?>
	</div>

	<div class="simple">
		<?php echo $form->labelEx($model, 'type'); ?>
		<?php echo $form->dropDownList($model, 'type', $model->typeOptions, array('prompt'=>'','style'=>'width:300px')); ?>
	</div>

	<div class="simple" id="content-container" style="display: none">
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
	</div>

	<div class="simple" id="url-container" style="display: none">
		<?php echo CHtml::activeLabel($model,'url',array('required'=>true)); ?>
		<?php echo $form->textField($model,'url', array('style'=>'width: 600px')); ?>
	</div>

	<div class="action">
		<?php echo CHtml::submitButton($model->isNewRecord ? Yii::t('PageModule.ui', 'Create') : Yii::t('PageModule.ui','Save'), array('class'=>$this->module->primaryButtonCssClass)); ?>
		<?php echo CHtml::link(Yii::t('PageModule.ui', 'Cancel'), $this->getReturnUrl() ? $this->getReturnUrl() : array('admin'), array('class'=>$this->module->secondaryButtonCssClass)); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->