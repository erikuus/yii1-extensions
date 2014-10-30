<?php
/**
 * XFAlert class file.
 *
 * Inserts alert box based on Foundation 5 CSS Framework.
 *
 * The following shows how to use XFAlert:
 *
 * In layout:
 * <pre>
 * $this->widget('ext.components.foundation.widgets.XFAlert');
 * </pre>
 *
 * In controller:
 * <pre>
 * Yii::app()->user->setFlash('success',Yii::t('ui','Data have been saved!'));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */

class XFAlert extends CWidget
{
	/**
	 * @var array the keys for which to get flash messages.
	 */
	public $keys=array('info','success','warning','error');

	/**
	 * @var string the template to use for displaying flash messages.
	 */
	public $template='<div data-alert class="alert-box {key}">{message}<a class="close" href="">&times;</a></div>';

	/**
	 * Runs the widget.
	 */
	public function run()
	{
		if(is_string($this->keys))
			$this->keys=array($this->keys);

		foreach($this->keys as $key)
		{
			if(Yii::app()->user->hasFlash($key))
			{
				echo strtr($this->template,array('{key}'=>$key,'{message}'=>Yii::app()->user->getFlash($key)));
			}
		}
	}
}