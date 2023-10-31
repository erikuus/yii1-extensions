<?php
/**
 * Tipsy jQuery Extension - jQuery plugin
 * @yiiVersion 1.1.6
 */

/**
 * Description of Tipsy
 * Per the http://onehackoranother.com/projects/jquery/tipsy/
 * @author Kamarul Ariffin Ismail <kamarul.ismail@gmail.com>
 * @version 1.1
 */

/**
 * Removed htmlOptions parameter: replaced CHtml::resolveNameID($model,$attribute,$htmlOptions) with CHtml::activeId($model,$attribute)
 * Removed init function to enable multiple instances of the widget
 * @author Erik Uus <erik.uus@gmail.com>
 */

/**
 * Example of Usage:
 *
 * $this->widget('ext.widgets.tipsy.XTipsy', array(
 *     'trigger'=>'hover',
 *     'items'=>array(
 *         array(
 *             'id'=>array('model'=>$model, 'attribute'=>'model_attribute_name'),
 *             'fallback'=>'Put custom tooltip here.'
 *         ),
 *     ),
 * ));
 *
 * When used with CGridView, add the following code:
 *
 * $this->widget('zii.widgets.grid.CGridView', array(
 *     'afterAjaxUpdate'=>'function(id,data){initTipsy();}',
 *  ...
 */
class XTipsy extends CWidget
{
	public $items=array();
	public $htmlOptions=array();

	public $className;
	public $delayIn;
	public $delayOut;
	public $fade;
	public $fallback;
	public $gravity;
	public $html;
	public $offset;
	public $opacity;
	public $title;
	public $trigger;
	public $live;

	private $_baseUrl;

	public function init()
	{
		// GET ASSETS PATH
		$assets=dirname(__FILE__).DIRECTORY_SEPARATOR.'assets';

		// PUBLISH FILES
		$this->_baseUrl=Yii::app()->assetManager->publish($assets);
	}

	public function run()
	{
		// REGISTER JS SCRIPT
		$cs=Yii::app()->clientScript;
		$cs->registerScriptFile($this->_baseUrl.'/jquery.tipsy.js');

		// REGISTER CSS
		$cs->registerCssFile($this->_baseUrl.'/css/tipsy.css');

		// LOOP THROUGH ITEMS
		$items=$this->items;
		$scriptList=array();
		foreach($items as $item)
		{
			$params=array();

			if(is_array($item['id']))
			{
				$model=$item['id']['model'];
				$attribute=$item['id']['attribute'];
				$tipsyID='#'.CHtml::activeId($model,$attribute);
			}
			else
				$tipsyID=$item['id'];

			if($this->className)
				$params['className']=$this->className;

			if(isset($item['delayIn']))
				$params['delayIn']=$item['delayIn'];
			elseif($this->delayIn)
				$params['delayIn']=$this->delayIn;
			else
				$params['delayIn']=50;

			if(isset($item['delayOut']))
				$params['delayOut']=$item['delayOut'];
			elseif($this->delayOut)
				$params['delayOut']=$this->delayOut;
			else
				$params['delayOut']=50;

			if(isset($item['fade']))
				$params['fade']=$item['fade'];
			elseif($this->fade)
				$params['fade']=$this->fade;

			if(isset($item['fallback']))
				$params['fallback']=$item['fallback'];
			elseif($this->fallback)
				$params['fallback']=$this->fallback;

			if(isset($item['gravity']))
				$params['gravity']=$item['gravity'];
			elseif($this->gravity)
				$params['gravity']=$this->gravity;

			if(isset($item['html']))
				$params['html']=$item['html'];
			elseif($this->html)
				$params['html']=$this->html;

			if(isset($item['offset']))
				$params['offset']=$item['offset'];
			elseif($this->offset)
				$params['offset']=$this->offset;

			if(isset($item['opacity']))
				$params['opacity']=$item['opacity'];
			elseif($this->opacity)
				$params['opacity']=$this->opacity;
			else
				$params['opacity']='0.8';

			if(isset($item['title']))
				$params['title']=$item['title'];
			elseif($this->title)
				$params['title']=$this->title;

			if(isset($item['trigger']))
				$params['trigger']=$item['trigger'];
			elseif($this->trigger)
				$params['trigger']=$this->trigger;
			else
				$params['trigger']='hover';

			if(isset($item['live']))
				$params['live']=$item['live'];
			elseif($this->live)
				$params['live']=$this->live;
			else
				$params['live']=false;

			// GENERATE JS CODE
			if(!empty($tipsyID))
			{
				$jsCode="\$('".$tipsyID."').tipsy(".CJavaScript::encode($params).");";
				$scriptList[]=$jsCode;
			}
		} //END foreach($items as $item)

		if(!empty($scriptList))
		{
			$tipsyID=$this->getId();
			$jsCode=implode("\n",$scriptList);
			$cs->registerScript(__CLASS__.'#'.$tipsyID,$jsCode,CClientScript::POS_END);
		}
	}
}
?>