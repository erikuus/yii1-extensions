<?php
/**
 * XAlert class file
 *
 * XAlert displays flash messages
 *
 * Example of usage:
 * <pre>
 * Yii::app()->user->setFlash('item.create.success',Yii::t('ui','All data successfully saved!'));
 * Yii::app()->user->setFlash('item.create.partial-success',Yii::t('ui','Some data of this item has been successfully saved!'));
 * Yii::app()->user->setFlash('item.update.locked',Yii::t('ui','Could not save data, beacause item is locked!'));
 * Yii::app()->user->setFlash('item.update.error',Yii::t('ui','An error occurred when trying to save item!'));
 * Yii::app()->user->setFlash('item.view.restricted',Yii::t('ui','You are not authorized to view all data!'));
 * Yii::app()->user->setFlash('error','<div class="errorMessage">An unknown error!</div>');
 *
 * $this->widget('ext.widgets.alert.XAlert',array(
 *     'alerts'=>array(
 *         'item.create.success'=>'success',
 *         'item.create.partial-success'=>'success',
 *         'item.update.locked'=>'error',
 *         'item.update.error'=>'error',
 *         'item.view.restricted'=>'warning',
 *         'error'=>null
 *     )
 * ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XAlert extends CWidget
{
	private $types=array();

	/**
	 * @var mixed the CSS file used for the widget. Defaults to null, meaning
	 * using the default CSS file included together with the widget.
	 * If false, no CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 */
	public $cssFile;
	/**
	 * @var string the CSS class for alert box. Defaults to 'alertBox'.
	 */
	public $boxCssClass='alertBox';
	/**
	 * @var string the CSS class for success message. Defaults to 'alertSuccessMessage'.
	 */
	public $successCssClass='alertSuccessMessage';
	/**
	 * @var string the CSS class for error message. Defaults to 'alertWarningMessage'.
	 */
	public $warningCssClass='alertWarningMessage';
	/**
	 * @var string the CSS class for error message. Defaults to 'alertErrorMessage'.
	 */
	public $errorCssClass='alertErrorMessage';
	/**
	 * @var array list of alerts. Each alert is specified as an array of name-value pairs.
	 * Possible option names include the following:
	 * <ul>
	 * <li>key: flash message key. It is passed to {@link CWebUser::hasFlash}.</li>
	 * <li>type: alert type. If it is not in the list of valid types, plain flash message is displayed.</li>
	 * </ul>
	 */
	public $alerts=array();

	/**
	 * Initializes the widget.
	 */
	public function init()
	{
		$this->registerClientScript();

		$this->types = array(
			'success'=>$this->successCssClass,
			'warning'=>$this->warningCssClass,
			'error'=>$this->errorCssClass
		);
	}

	/**
	 * Finishes rendering the portlet.
	 * This renders the body part of the portlet, if it is visible.
	 */
	public function run()
	{
		foreach($this->alerts as $key=>$type)
		{
			if(Yii::app()->user->hasFlash($key))
			{
				$message=Yii::app()->user->getFlash($key);
				if(in_array($type, array_keys($this->types)))
					echo "<div class=\"{$this->boxCssClass} {$this->types[$type]}\">{$message}<a class=\"close\" href=\"\">&times;</a></div>";
				else
					echo $message;
			}
		}
	}

	/**
	 * Register necessary client scripts.
	 */
	protected function registerClientScript()
	{
		$cs=Yii::app()->clientScript;
		$cs->registerCoreScript('jquery');

		// publish and register css files
		if($this->cssFile===null)
		{
			$cssFile=CHtml::asset(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'alert.css');
			$cs->registerCssFile($cssFile);
		}
		else if($this->cssFile!==false)
			$cs->registerCssFile($this->cssFile);

		// register inline javascript
		$script =
<<<SCRIPT
	jQuery(".{$this->boxCssClass}").delegate("a.close", "click", function(event) {
		event.preventDefault();
		$(this).closest(".$this->boxCssClass").fadeOut(function(event){
			$(this).remove();
		});
	});
SCRIPT;

		$cs->registerScript(__CLASS__, $script, CClientScript::POS_READY);
	}
}