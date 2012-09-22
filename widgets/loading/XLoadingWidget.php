<?php

/**
 * Loading widget class file.
 *
 * @author Vitaliy Stepanenko <mail@vitaliy.in>
 * @copyright Copyright &copy; 2011 Vitaliy Stepanenko
 * @license BSD
 *
 * @link http://www.yiiframework.com/extension/loading/
 *
 * @package widgets.loading
 * @version $Id:$ (1.0)
 */

/**
 * Added image preloading functionality
 * @author Erik Uus <erik.uus@gmail.com>
 */

/**
 * Example by Erik Uus <erik.uus@gmail.com>
 * <pre>
 *     $this->widget('ext.widgets.loading.XLoadingWidget');
 *     $form=$this->beginWidget('CActiveForm', array(
 *         'id'=>'some-form',
 *         'enableAjaxValidation'=>true,
 *         'clientOptions'=> array(
 *              'validateOnSubmit'=>true,
 *              'afterValidate'=>'js:
 *                  function(form, data, hasError){
 *                      if(!hasError) {
 *                          Loading.show();
 *                          // end here with "return true;" if using regular submit
 *                          $.fn.yiiListView.update("some-list", {
 *                              type:"POST",
 *                              url:form.attr("action"),
 *                              data:form.serialize(),
 *                              success:function() {
 *                                  $.fn.yiiListView.update("some-list");
 *                              }
 *                          });
 *                          Loading.hide();
 *                      }
 *                  }
 *              '
 *         )
 *     ));
 * </pre>
 */

class XLoadingWidget extends CWidget
{
	private static $included = false;

	public function run()
	{
		if (self::$included) return;
		self::$included = true;
		$assetsPath = $this->getViewPath(true) . '/assets';
		$assetsUrl = Yii::app()->assetManager->publish($assetsPath, false, -1, YII_DEBUG);
		Yii::app()->clientScript
			->registerCoreScript('jquery')
			->registerCssFile($assetsUrl . '/Loading.css')
			->registerScriptFile($assetsUrl . '/Loading.js')
			->registerScript(__CLASS__, "
				if (document.images) {
					XLoadingWidgetImg = new Image();
					XLoadingWidgetImg.src = '".$assetsUrl."/loading.gif';
				}
			", CClientScript::POS_READY);
	}
}