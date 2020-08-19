<?php
/**
 * EAuthWidget class file.
 *
 * @version 1.0.0
 * @author Maxim Zemskov <nodge@yandex.ru>
 * @link http://github.com/Nodge/yii-eauth/
 * @license http://www.opensource.org/licenses/bsd-license.php
 *
 * @version 1.1.0
 * @author Erik Uus <erik.uus@gmail.com>
 */

/**
 * The EAuthWidget widget prints buttons to authenticate user with OpenID and OAuth providers.
 *
 * @package application.extensions.eauth
 */
class EAuthWidget extends CWidget
{
	/**
	 * @var string EAuth component name.
	 */
	public $component='eauth';
	/**
	 * @var array the services
	 * @see EAuth::getServices()
	 */
	public $services;
	/**
	 * @var array predefined service names. If not set then all services are used.
	 */
	public $predefinedServices;
	/**
	 * @var boolean whether to use popup window for authorization dialog.
	 */
	public $popup;
	/**
	 * @var string the action to use for dialog destination.
	 * Defaults to current route.
	 */
	public $action;
	/**
	 * @var mixed the CSS file used for the widget. Defaults to null, meaning
	 * using the default CSS file included together with the widget.
	 * If false, no CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 */
	public $cssFile;
	/**
	 * @var string the HTML tag name for the widget container. Defaults to 'div'.
	 */
	public $containerTagName='div';
	/**
	 * Default CSS class for the widget container. Defaults to 'services'.
	 */
	public $containerCssClass='services';
	/**
	 * @var array HTML attributes for widget container
	 */
	public $containerHtmlOptions=array();
	/**
	 * @var string the HTML tag name for the list of all services. Defaults to 'ul'.
	 */
	public $listTagName='ul';
	/**
	 * Default CSS class for the list of all services. Defaults to 'auth-services'.
	 */
	public $listCssClass='auth-services';
	/**
	 * @var array HTML attributes for the list of all services.
	 */
	public $listHtmlOptions=array();
	/**
	 * @var string the HTML tag name for the service item. Defaults to 'li'.
	 */
	public $itemTagName='li';
	/**
	 * Default CSS class for the service item. Defaults to 'auth-service'.
	 */
	public $itemCssClass='auth-service';
	/**
	 * @var array HTML attributes for the service item.
	 */
	public $itemHtmlOptions=array();

	/**
	 * Default CSS class for the service item link. Defaults to 'auth-link'.
	 */
	public $linkCssClass='auth-link';
	/**
	 * @var array HTML attributes for the service item link.
	 */
	public $linkHtmlOptions=array();
	/**
	 * @var string the template used to render a service item link. In this template,
	 * the token "{icon}" will be replaced with the icon tag and token "{title}" with
	 * title tag. If this property is not set, icon will be rendered before title
	 * without any decoration.
	 */
	public $linkTemplate;
	/**
	 * @var string the HTML tag name for the service item icon. Defaults to 'span'.
	 */
	public $iconTagName='span';
	/**
	 * @var string the HTML tag content for the service item icon. Defaults to '<i></i>'.
	 */
	public $iconTagContent='<i></i>';
	/**
	 * Default CSS class for the service item icon. Defaults to 'auth-icon'.
	 */
	public $iconCssClass='auth-icon';
	/**
	 * @var array the list that matches service id to css class that will be added to icon tag.
	 * This can be used to display special font icons for every service instead of image icons.
	 */
	public $iconCssClassMap=array();
	/**
	 * @var array HTML attributes for the service item icon.
	 */
	public $iconHtmlOptions=array();
	/**
	 * @var string the HTML tag name for the service item title. Defaults to 'span'.
	 */
	public $titleTagName='span';
	/**
	 * Default CSS class for the service item title. Defaults to 'auth-title'.
	 */
	public $titleCssClass='auth-title';
	/**
	 * @var array HTML attributes for the service item icon.
	 */
	public $titleHtmlOptions=array();

	/**
	 * Initializes the widget.
	 */
	public function init()
	{
		// get component
		$component=Yii::app()->getComponent($this->component);

		// if not set, get all services defined in component configuration
		if(!isset($this->services))
			$this->services=$component->getServices();

		// filter services by predefined service names
		if(is_array($this->predefinedServices))
		{
			$services=array();
			foreach($this->predefinedServices as $serviceName)
			{
				if(isset($this->services[$serviceName]))
					$services[$serviceName] = $this->services[$serviceName];
			}
			$this->services=$services;
		}

		// if not set, get popup value defined in component configuration
		if(!isset($this->popup))
			$this->popup=$component->popup;

		// if not set, use current route
		if(!isset($this->action))
			$this->action=Yii::app()->urlManager->parseUrl(Yii::app()->request);

		// prepare html options
		$this->prepareHtmlOptions();

		// register client scripts
		$this->registerClientScript();
	}

	/**
	 * Renders the widget.
	 */
	public function run()
	{
		echo CHtml::openTag($this->containerTagName, $this->containerHtmlOptions);
			echo CHtml::openTag($this->listTagName, $this->listHtmlOptions);
			foreach($this->services as $name=>$service)
			{
				// add service specific classes
				$itemHtmlOptions=$this->itemHtmlOptions;
				$itemHtmlOptions['class'].=' '.$service->id;

				$linkHtmlOptions=$this->linkHtmlOptions;
				$linkHtmlOptions['class'].=' '.$service->id;

				$iconHtmlOptions=$this->iconHtmlOptions;
				if(isset($this->iconCssClassMap[$service->id]))
					$iconHtmlOptions['class'].=' '.$this->iconCssClassMap[$service->id];
				else
					$iconHtmlOptions['class'].=' '.$service->id;

				// build link label from icon and title tag
				$icon=CHtml::openTag($this->iconTagName, $iconHtmlOptions).$this->iconTagContent.CHtml::closeTag($this->iconTagName);
				$title=CHtml::tag($this->titleTagName, $this->titleHtmlOptions, $service->title);

				if($this->linkTemplate)
					$label=strtr($this->linkTemplate,array('{icon}'=>$icon,'{title}'=>$title));
				else
					$label=$icon.$title;

				// print item
				echo CHtml::openTag($this->itemTagName, $itemHtmlOptions);
					echo CHtml::link($label, array($this->action, 'service'=>$name), $linkHtmlOptions);
				echo CHtml::closeTag($this->itemTagName);
			}
			echo CHtml::closeTag($this->listTagName);
		echo CHtml::closeTag($this->containerTagName);
	}

	/**
	 * Add class names to html options
	 */
	protected function prepareHtmlOptions()
	{
		if(!isset($this->containerHtmlOptions['class']))
			$this->containerHtmlOptions=array_merge($this->containerHtmlOptions, array('class'=>$this->containerCssClass));
		else
			$this->containerHtmlOptions['class'].=' '.$this->containerCssClass;

		if(!isset($this->listHtmlOptions['class']))
			$this->listHtmlOptions=array_merge($this->listHtmlOptions, array('class'=>$this->listCssClass));
		else
			$this->listHtmlOptions['class'].=' '.$this->listCssClass;

		if(!isset($this->itemHtmlOptions['class']))
			$this->itemHtmlOptions=array_merge($this->itemHtmlOptions, array('class'=>$this->itemCssClass));
		else
			$this->itemHtmlOptions['class'].=' '.$this->itemCssClass;

		if(!isset($this->iconHtmlOptions['class']))
			$this->iconHtmlOptions=array_merge($this->iconHtmlOptions, array('class'=>$this->iconCssClass));
		else
			$this->iconHtmlOptions['class'].=' '.$this->iconCssClass;

		if(!isset($this->titleHtmlOptions['class']))
			$this->titleHtmlOptions=array_merge($this->titleHtmlOptions, array('class'=>$this->titleCssClass));
		else
			$this->titleHtmlOptions['class'].=' '.$this->titleCssClass;

		if(!isset($this->linkHtmlOptions['class']))
			$this->linkHtmlOptions=array_merge($this->linkHtmlOptions, array('class'=>$this->linkCssClass));
		else
			$this->linkHtmlOptions['class'].=' '.$this->linkCssClass;
	}

	/**
	 * Register necessary client scripts.
	 */
	protected function registerClientScript()
	{
		// register core script
		$cs=Yii::app()->clientScript;
		$cs->registerCoreScript('jquery');

		// publish
		$assets=dirname(__FILE__).DIRECTORY_SEPARATOR.'assets';
		$url=Yii::app()->assetManager->publish($assets, false, -1, YII_DEBUG);

		// register css file
		if($this->cssFile===null)
			$cs->registerCssFile($url.'/css/auth.css');
		elseif($this->cssFile!==false)
			$cs->registerCssFile($this->cssFile);

		// register javascript to open the authorization dilalog in popup window.
		if($this->popup)
		{
			$cs->registerScriptFile($url.'/js/auth.js', CClientScript::POS_END);
			$js='';
			foreach($this->services as $name=>$service)
			{
				$args=$service->jsArguments;
				$args['id']=$service->id;
				$js.='$(".auth-service.'.$service->id.' a").eauth('.json_encode($args).');'."\n";
			}
			$cs->registerScript(__CLASS__, $js, CClientScript::POS_READY);
		}
	}
}
