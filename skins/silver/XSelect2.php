<?php
/**
 * Global defination for XSelect2
 */
return array(
	'default'=>array(
		'options'=>array(
			'formatInputTooShort'=>'js:function (input, min) {
				var n = min - input.length;
				return "'.Yii::t('XSelect2.select2','Please enter ').'" + n + "'.Yii::t('XSelect2.select2',' more character').'" + (n == 1? "" : "'.Yii::t('XSelect2.select2','s').'");
			}',
			'formatNoMatches'=>'js:function () {
				return "'.Yii::t('XSelect2.select2', 'No matches found').'";
			}',
			'formatLoadMore'=>'js:function (pageNumber) {
				return "'.Yii::t('XSelect2.select2', 'Loading more results...').'";
			}',
			'formatSearching'=>'js:function () {
				return "'.Yii::t('XSelect2.select2', 'Searching...').'";
			}',
		)
	)
);