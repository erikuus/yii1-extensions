<?php Yii::app()->clientScript->registerCss('hardCodedStyle', "
	#menu-form input.menu-title {width: 400px}
	#menu-form input.menu-url {width: 320px}
	#menu-form div.content {margin: 0 0 20px 100px}
	#menu-form .menu-type-option {margin: 0 5px 20px 50px}
	#menu-form .menu-type-option + label {margin: 0; font-weight: normal; display: inline}
", 'screen', CClientScript::POS_HEAD); ?>

<div class="form" style="width: auto">

<?php $form=$this->beginWidget('ext.widgets.form.XDynamicForm', array(
	'id'=>'menu-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p><?php echo Yii::t('PageModule.ui', 'Fields with {mark} are required',
	array('{mark}'=>'<span class="required">*</span>')); ?></p>

	<?php echo $form->errorSummary($model); ?>

	<?php $this->beginContent('/decorators/formRow')?>
		<?php echo $form->labelEx($model,'title'); ?>
		<?php echo $form->textField($model,'title',array('class'=>'menu-title')); ?>
		<?php echo $form->error($model,'title'); ?>
	<?php $this->endContent()?>

	<?php $radio=$form->explodeRadioButtonList($model, 'type', $model->typeOptions, 'menu-type-option'); ?>

	<?php $this->beginContent('/decorators/formRow')?>
		<?php echo $form->labelEx($model,'type'); ?>
		<?php $form->staticArea($radio[PageMenu::TYPE_HEADER]); ?>
		<?php $form->beginDynamicArea($radio[PageMenu::TYPE_ITEM]); ?>
			<div class="content">
				<?php echo $form->labelEx($model,'content'); ?>
				<?php $this->widget('ext.widgets.xheditor.XHeditor',array(
					'model'=>$model,
					'modelAttribute'=>'content',
					'config'=>array(
						'id'=>CHtml::activeId($model,'content'),
						'loadCSS'=>$this->module->editorSideContentCssFile ? $this->module->editorSideContentCssFile : null,
						'tools'=>$this->module->editorMenuTools,
						'width'=>'300px',
						'height'=>'400px',
						'upImgUrl'=>$this->createUrl('request/uploadFile'),
						'upImgExt'=>$this->module->editorUploadAllowedImageExtensions,
						'upLinkUrl'=>$this->createUrl('request/uploadFile'),
						'upLinkExt'=>$this->module->editorUploadAllowedLinkExtensions,
					)
				));?>
				<?php echo $form->error($model,'content'); ?>
			</div>
		<?php $form->endDynamicArea(); ?>
		<?php $form->beginDynamicArea($radio[PageMenu::TYPE_LINK]); ?>
			<?php echo $form->textField($model,'url',array('class'=>'menu-url')); ?>
			<?php echo $form->error($model,'url'); ?>
		<?php $form->endDynamicArea(); ?>
		<?php echo $form->error($model,'type'); ?>
	<?php $this->endContent()?>

	<?php $this->beginContent('/decorators/formButtonsRow')?>
		<?php echo CHtml::submitButton($model->isNewRecord ? Yii::t('PageModule.ui', 'Create') : Yii::t('PageModule.ui','Save'), array('class'=>$this->module->primaryButtonCssClass)); ?>
		<?php echo CHtml::link(Yii::t('PageModule.ui', 'Cancel'), $this->getReturnUrl() ? $this->getReturnUrl() : array('admin'), array('class'=>$this->module->secondaryButtonCssClass)); ?>
	<?php $this->endContent()?>

<?php $this->endWidget(); ?>

</div><!-- form -->