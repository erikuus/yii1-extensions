<?php
$this->pageTitle=Yii::app()->name . ' - ' . Yii::t('UserModule.ui', 'Update Data');
$this->breadcrumbs=array(
	Yii::t('UserModule.ui', 'Users')=>$this->getReturnUrl(),
	Yii::t('UserModule.ui', 'Update Data'),
);
?>

<h2>
<?php echo Yii::t('UserModule.ui', 'Update Data'); ?>
</h2>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>