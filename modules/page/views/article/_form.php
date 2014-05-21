<?php
$cs=Yii::app()->clientScript;
$cs->registerCssFile($this->getAsset('/css/page.css'));
$cs->registerCssFile($this->getAsset('/css/xheditor.css'));
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'article-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p><?php echo Yii::t('PageModule.ui', 'Fields with {mark} are required',
	array('{mark}'=>'<span class="required">*</span>')); ?>
</p>

	<?php echo $form->errorSummary($model); ?>

	<?php $this->beginContent('/decorators/formSimpleRow')?>
		<?php echo $form->labelEx($model,'menu_id'); ?>
		<?php echo $form->DropDownList($model,'menu_id',PageMenu::model()->activeItemOptions,array('prompt'=>'','style'=>'width:250px'));?>
		<?php echo $form->error($model,'menu_id'); ?>
	<?php $this->endContent()?>

	<?php $this->beginContent('/decorators/formSimpleRow')?>
		<?php echo $form->labelEx($model,'title'); ?>
		<?php echo $form->textField($model,'title',array('style'=>'width:725px;')); ?>
		<?php echo $form->error($model,'title'); ?>
	<?php $this->endContent()?>

	<?php $this->beginContent('/decorators/formSimpleRow')?>
		<?php echo $form->labelEx($model,'content'); ?>
		<?php $this->widget('ext.widgets.xheditor.XHeditor',array(
			'model'=>$model,
			'modelAttribute'=>'content',
			'config'=>array(
				'id'=>CHtml::activeId($model,'content'),
				'loadCSS'=>$this->getAsset('/css/editor_article_content.css'),
				'tools'=>$this->module->editorArticleTools,
				'width'=>'730px',
				'height'=>'600px',
				'upImgUrl'=>$this->createUrl('request/uploadFile'),
				'upImgExt'=>$this->module->editorUploadAllowedImageExtensions,
				'upLinkUrl'=>$this->createUrl('request/uploadFile'),
				'upLinkExt'=>$this->module->editorUploadAllowedExtensions,
				'plugins'=>array(
					'Template'=>array(
						'c'=>'xheditorTemplateIcon', // css class form plugin icon on editor toolbar
						't'=>Yii::t('PageModule.ui', 'Insert Template'), // icon title
						's'=>'ctrl+1', // plugin shortcut
						'e'=>"js:function(){
							var _this=this ;
							_this.saveBookmark();
							_this.showIframeModal('Select Template' , '".$this->createUrl('template')."' , function(v){_this.loadBookmark(); _this.pasteHTML(v);}, 500, 300);
						}"
					)
				)
			)
		));?>
		<?php echo $form->error($model,'content'); ?>
	<?php $this->endContent()?>

	<?php $this->beginContent('/decorators/formButtonsRow')?>
		<?php echo CHtml::submitButton($model->isNewRecord ? Yii::t('PageModule.ui', 'Create') : Yii::t('PageModule.ui','Save'), array('class'=>$this->module->primaryButtonCssClass)); ?>
		<?php echo CHtml::link(Yii::t('PageModule.ui', 'Cancel'), $this->getReturnUrl() ? $this->getReturnUrl() : array('admin'), array('class'=>$this->module->secondaryButtonCssClass)); ?>
	<?php $this->endContent()?>

<?php $this->endWidget(); ?>

</div><!-- form -->