<div class="form">

<?php $form=$this->beginWidget('ext.widgets.form.XDynamicForm', array(
	'id'=>'menu-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p><?php echo Yii::t('PageModule.ui', 'Fields with {mark} are required',
	array('{mark}'=>'<span class="required">*</span>')); ?></p>

	<?php echo $form->errorSummary($model); ?>

	<?php $this->beginContent('/decorators/formSimpleRow')?>
		<?php echo $form->labelEx($model,'title'); ?>
		<?php echo $form->textField($model,'title',array('style'=>'width:400px')); ?>
		<?php echo $form->error($model,'title'); ?>
	<?php $this->endContent()?>

	<?php $radio=$form->explodeRadioButtonList($model, 'type', $model->typeOptions); ?>

	<div class="complex" style="margin-top: 10px">
		<span>
			<?php echo $form->labelEx($model,'type'); ?>
		</span>
		<div class="panel">
			<?php $form->staticArea($radio[PageMenu::TYPE_HEADER]); ?>
			<?php $form->beginDynamicArea($radio[PageMenu::TYPE_ITEM]); ?>
				<div class="complex">
					<span>
						<?php echo $form->labelEx($model,'content'); ?>
					</span>
					<div class="panel">
						<?php $this->widget('ext.widgets.xheditor.XHeditor',array(
							'model'=>$model,
							'modelAttribute'=>'content',
							'config'=>array(
								'id'=>CHtml::activeId($model,'content'),
								'loadCSS'=>$this->getAsset('/css/editor_menu_content.css'),
								'tools'=>$this->module->editorMenuTools,
								'width'=>'300px',
								'height'=>'400px',
								'upImgUrl'=>$this->createUrl('request/uploadFile'),
								'upImgExt'=>$this->module->editorUploadAllowedImageExtensions,
								'upLinkUrl'=>$this->createUrl('request/uploadFile'),
								'upLinkExt'=>$this->module->editorUploadAllowedExtensions,
							)
						));?>
						<?php echo $form->error($model,'content'); ?>
					</div>
				</div><!-- complex -->
			<?php $form->endDynamicArea(); ?>
			<?php $form->beginDynamicArea($radio[PageMenu::TYPE_LINK]); ?>
				<?php echo $form->textField($model,'url',array('style'=>'width:300px')); ?>
				<?php echo $form->error($model,'url'); ?>
			<?php $form->endDynamicArea(); ?>
			<?php echo $form->error($model,'type'); ?>
		</div>
	</div>

	<?php $this->beginContent('/decorators/formButtonsRow')?>
		<?php echo CHtml::submitButton($model->isNewRecord ? Yii::t('PageModule.ui', 'Create') : Yii::t('PageModule.ui','Save'), array('class'=>$this->module->primaryButtonCssClass)); ?>
		<?php echo CHtml::link(Yii::t('PageModule.ui', 'Cancel'), $this->getReturnUrl() ? $this->getReturnUrl() : array('admin'), array('class'=>$this->module->secondaryButtonCssClass)); ?>
	<?php $this->endContent()?>

<?php $this->endWidget(); ?>

</div><!-- form -->