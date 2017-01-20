<?php
/**
 * Global defination for XSelect2
 */
return array(
	'default'=>array(
		'options'=>array(
			'formatInputTooShort'=>'js:function (input, min) { var n = min - input.length; return "'.Yii::t('ui','Please enter ').'" + n + "'.Yii::t('ui',' more character').'" + (n == 1? "" : "'.Yii::t('ui','s').'"); }',
		)
	)
);