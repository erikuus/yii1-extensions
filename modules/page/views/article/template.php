<?php
$cs=Yii::app()->clientScript;
$cs->registerCoreScript('jquery');
$cs->registerScriptFile($this->getAsset('/js/xheditor.plugin.js'), CClientScript::POS_HEAD);

$cs->registerScript("insertTemplate", "
	$('a').click(function(event) {
		event.preventDefault();
		var templateName = $(this).attr('href');
		var templateContent = $('#'+templateName).val()
		callback(templateContent);
	});
", CClientScript::POS_READY);

$cs->registerCss('imageStyle', "
    img {
       margin: 20px 0 0 100px;
    }
", 'screen', CClientScript::POS_HEAD);
?>

<?php echo CHtml::link(CHtml::image($this->getAsset('/images/50-50-layout.png'),Yii::t('PageModule.ui','2 Columns (50/50)')),'template-50-50')?>
<?php echo CHtml::link(CHtml::image($this->getAsset('/images/3-col-layout.png'),Yii::t('PageModule.ui','3 Columns')),'template-3-col')?>
<?php echo CHtml::link(CHtml::image($this->getAsset('/images/30-70-layout.png'),Yii::t('PageModule.ui','2 Columns (30/70)')),'template-30-70')?>
<?php echo CHtml::link(CHtml::image($this->getAsset('/images/70-30-layout.png'),Yii::t('PageModule.ui','2 Columns (70/30)')),'template-70-30')?>


<textarea id="template-50-50" style="display:none">
	<table class="layout" width="100%" cellspacing="0" cellpadding="0">
		<tbody>
			<tr>
				<td width="50%">&nbsp;</td>
				<td width="50%">&nbsp;</td>
			</tr>
		</tbody>
	</table>
</textarea>

<textarea id="template-30-70" style="display:none">
	<table class="layout" width="100%" cellspacing="0" cellpadding="0">
		<tbody>
			<tr>
				<td width="30%">&nbsp;</td>
				<td width="70%">&nbsp;</td>
			</tr>
		</tbody>
	</table>
</textarea>

<textarea id="template-70-30" style="display:none">
	<table class="layout" width="100%" cellspacing="0" cellpadding="0">
		<tbody>
			<tr>
				<td width="70%">&nbsp;</td>
				<td width="30%">&nbsp;</td>
			</tr>
		</tbody>
	</table>
</textarea>

<textarea id="template-3-col" style="display:none">
	<table class="layout" width="100%" cellspacing="0" cellpadding="0">
		<tbody>
			<tr>
				<td width="33%">&nbsp;</td>
				<td width="33%">&nbsp;</td>
				<td width="33%">&nbsp;</td>
			</tr>
		</tbody>
	</table>
</textarea>