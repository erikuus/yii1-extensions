<?php
/**
 * XTabularInput
 *
 * Widget to handle variable number of form inputs.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XTabularInput extends CWidget
{
	/**
	 * @var array models for tabular input.
	 */
	public $models=array();
	/**
	 * @var string the view used for rendering each tabular input.
	 */
	public $inputView;
	/**
	 * @var string the url to action that (partial)renders tabular input.
	 *
	 * Example:
	 *
	 * public function actionField($index)
	 * {
	 *     $model=new Task;
	 *     $this->renderPartial('_field', array('model'=>$model,'index'=>$index));
	 * }
	 */
	public $inputUrl;
	/**
	 * @var string the CSS class for the widget container. Defaults to 'tabular-container'.
	 */
	public $containerCssClass='tabular-container';
	/**
	 * @var string the CSS class for container that holds all inputs. Defaults to 'tabular-input-container'.
	 */
	public $inputContainerCssClass='tabular-input-container';
	/**
	 * @var string the CSS class for the tabular input. Defaults to 'tabular-input'.
	 */
	public $inputCssClass='tabular-input';
	/**
	 * @var string the CSS class for the hidden elements that hold variable inputs indexes. Defaults to 'tabular-input-index'.
	 */
	public $indexCssClass='tabular-input-index';
	/**
	 * @var string the CSS class for the tabular inputs header. Defaults to 'tabular-header'.
	 */
	public $headerCssClass='tabular-header';
	/**
	 * @var string the CSS class for the element that adds inputs. Defaults to 'tabular-add'.
	 */
	public $addCssClass='tabular-input-add';
	/**
	 * @var string the CSS class for the element that removes inputs. Defaults to 'tabular-remove'.
	 */
	public $removeCssClass='tabular-input-remove';
	/**
	 * @var string the HTML tag name for the widget container. Defaults to 'div'.
	 */
	public $containerTagName='div';
	/**
	 * @var string the HTML tag name for the container of all tabular inputs. Defaults to 'div'.
	 */
	public $inputContainerTagName='div';
	/**
	 * @var string the HTML tag name for the container of all tabular inputs. Defaults to 'div'.
	 */
	public $headerTagName='div';
	/**
	 * @var string the HTML tag name for the container of tabular input. Defaults to 'div'.
	 */
	public $inputTagName='div';
	/**
	 * @var string the text or html of header.
	 */
	public $header;
	/**
	 * @var string the text of the link that adds inputs. Defaults to 'Add'.
	 */
	public $addLabel='Add';

	/**
	 * Initializes the widget.
	 * This renders the header part of the widget, if it is visible.
	 */
	public function init()
	{
		$this->registerClientScript();
		echo CHtml::openTag($this->containerTagName,array('class'=>$this->containerCssClass));
		if($this->header)
			echo CHtml::tag($this->headerTagName,array('class'=>$this->headerCssClass), $this->header);
		echo CHtml::openTag($this->inputContainerTagName,array('class'=>$this->inputContainerCssClass));
	}

	/**
	 * Finishes rendering the portlet.
	 * This renders the body part of the portlet, if it is visible.
	 */
	public function run()
	{
		$this->renderContent();
		echo CHtml::closeTag($this->inputContainerTagName);
		echo CHtml::link($this->addLabel, '#', array('class'=>$this->addCssClass));
		echo CHtml::closeTag($this->containerTagName);
	}

	/**
	 * Publish and register necessary client scripts.
	 */
	protected function registerClientScript()
	{
		// register core script
		$cs=Yii::app()->clientScript;
		$cs->registerCoreScript('jquery');

		// publish and register assets file
		$assets=Yii::app()->assetManager->publish(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');
		$cs->registerScriptFile($assets.'/jquery.calculation.min.js', CClientScript::POS_HEAD);

		// define values to be used inside script
		$openInputTag=CHtml::openTag($this->inputTagName,array('class'=>$this->inputCssClass));
		$closeInputTag=CHtml::closeTag($this->inputTagName);

		// register inline javascript
		$script =
<<<SCRIPT
	$(".{$this->addCssClass}").click(function(event){
		event.preventDefault();
		var input = $(this).parents(".{$this->containerCssClass}:first").children(".{$this->inputContainerCssClass}");
		var index = input.find(".{$this->indexCssClass}").length>0 ? input.find(".{$this->indexCssClass}").max()+1 : 0;
		$.ajax({
			success: function(html){
				input.append('{$openInputTag}'+html+'<input type="hidden" class="{$this->indexCssClass}" value="'+index+'" />{$closeInputTag}');
				input.siblings('.{$this->headerCssClass}').show();
			},
			type: 'get',
			url: '{$this->inputUrl}',
			data: {
				index: index
			},
			cache: false,
			dataType: 'html'
		});
	});
	$(".{$this->removeCssClass}").live("click", function(event) {
		event.preventDefault();
		$(this).parents(".{$this->inputCssClass}:first").remove();
		$('.{$this->inputContainerCssClass}').filter(function(){return $.trim($(this).text())===''}).siblings('.{$this->headerCssClass}').hide();
	});
SCRIPT;

		$cs->registerScript(__CLASS__, $script, CClientScript::POS_READY);
	}

	/**
	 * Renders the body part of the widget.
	 * Child classes should override this method to provide customized body content.
	 */
	protected function renderContent()
	{
		foreach($this->models as $index=>$model)
		{
			echo CHtml::openTag($this->inputTagName, array('class'=>$this->inputCssClass));
			$this->controller->renderPartial($this->inputView, array('model'=>$model, 'index'=>$index));
			echo "<input type=\"hidden\" class=\"{$this->indexCssClass}\" value=\"{$index}\" />";
			echo CHtml::closeTag($this->inputTagName);
		}
	}
}