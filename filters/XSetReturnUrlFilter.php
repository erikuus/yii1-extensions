<?php

/**
 * SetReturnUrl Filter
 *
 * Keep current URL (if it's not an AJAX url) in session so that the browser may
 * be redirected back.
 *
 * Configure application (config/main.php):
 * <pre>
 * return array(
 *     'import'=>array(
 *         'ext.filters.XSetReturnUrlFilter',
 *     ),
 * );
 * </pre>
 *
 * In controller implement filters() method:
 * <pre>
 * function filters() {
 *     return array(
 *         array(
 *             'XSetReturnUrlFilter',
 *             // Use for spcified actions (index and view):
 *             // 'XSetReturnUrlFilter + index, view',
 *         ),
 *     );
 * }
 * </pre>
 *
 * Usage:
 * <pre>
 * $this->redirect(Yii::app()->user->returnUrl);
 * </pre>
 *
 * @version 1.0.2
 * @author creocoder <creocoder@gmail.com>
 */

class XSetReturnUrlFilter extends CFilter
{

	protected function preFilter($filterChain)
	{
		$app=Yii::app();
		$request=$app->getRequest();

		if(!$request->getIsAjaxRequest())
			$app->getUser()->setReturnUrl($request->getUrl());

		return true;
	}
}