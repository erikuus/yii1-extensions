<?php
class PageController extends Controller
{
	/**
	 * @var array the breadcrumbs of the current page.
	 */
	public $breadcrumbs=array();

	/**
	 * @return array behaviors
	 */
	public function behaviors()
	{
		return array(
			'returnable'=>array(
				'class'=>'ext.behaviors.XReturnableBehavior',
			),
		);
	}

	/**
	 * @return boolean whether admin access is available
	 */
	public function isAdminAccess()
	{
		if(!Yii::app()->user->isGuest && (Yii::app()->user->name=='admin' || Yii::app()->user->checkAccess($this->module->authItemName)))
			return true;
		else
			return false;
	}

	/**
	 * @param string path to file relative to asset folder
	 * @return string url to asset
	 */
	public function getAsset($file)
	{
		return $this->getModule()->baseScriptUrl.$file;
	}
}