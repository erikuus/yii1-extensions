<?php
$cs=Yii::app()->clientScript;

if($this->module->formCssFile===null)
	$cs->registerCssFile($this->getAsset('/css/form.css'));
else if($this->module->formCssFile!==false)
	$cs->registerCssFile($this->module->formCssFile);

$cs->registerCssFile($this->getAsset('/css/xheditor.css'));
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'article-form',
	'enableAjaxValidation'=>false,
)); ?>

	<?php echo $form->errorSummary($model); ?>

	<div class="simple">
		<?php echo $form->labelEx($model,'menu_id'); ?>
		<?php echo $form->DropDownList($model,'menu_id',PageMenu::model()->activeItemOptions,array('prompt'=>'','style'=>'width:250px'));?>
	</div>

	<div class="simple">
		<?php echo $form->labelEx($model,'title'); ?>
		<?php echo $form->textField($model,'title',array('style'=>'width:725px;')); ?>
	</div>

	<div class="simple">
		<?php echo $form->labelEx($model,'content'); ?>
		<?php $this->widget('ext.widgets.xheditor.XHeditor',array(
			'model'=>$model,
			'modelAttribute'=>'content',
			'config'=>array(
				'id'=>CHtml::activeId($model,'content'),
				'loadCSS'=>$this->module->editorArticleCssFile ? $this->module->editorArticleCssFile : null,
				'tools'=>$this->module->editorArticleTools,
				'width'=>'730px',
				'height'=>'600px',
				'upImgUrl'=>$this->createUrl('request/uploadFile'),
				'upImgExt'=>$this->module->editorUploadAllowedImageExtensions,
				'upLinkUrl'=>$this->createUrl('request/uploadFile'),
				'upLinkExt'=>$this->module->editorUploadAllowedLinkExtensions,
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
	</div>

	<div class="action">
		<?php echo CHtml::submitButton($model->isNewRecord ? Yii::t('PageModule.ui', 'Create') : Yii::t('PageModule.ui','Save'), array('class'=>$this->module->primaryButtonCssClass)); ?>
		<?php echo CHtml::link(Yii::t('PageModule.ui', 'Cancel'), $this->getReturnUrl() ? $this->getReturnUrl() : array('admin'), array('class'=>$this->module->secondaryButtonCssClass)); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->