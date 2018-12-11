<?php
/**
 * XTawkMessenger class file
 *
 * Widget to implement tawk.to messaging app
 *
 * Tawk.to is a free messaging app that lets you monitor and chat with visitors on your website, mobile app or from a free customizable page.
 *
 * The following shows how to use XTawkMessenger.
 *
 * BASIC EXAMPLE
 * widget is allways visible
 *
 * <pre>
 * $this->widget('ext.widgets.tawk.XTawkMessenger', array(
 *     'source'=>'https://embed.tawk.to/123456789/default',
 * ));
 * </pre>
 *
 * ADVANCED EXAMPLE
 * widget is visible for quests only
 * widget is visible on given routes
 * widget is visible on Mon-Fri 09:00-17:00
 * widget is not visible on given holidays
 *
 * <pre>
 * $this->widget('ext.widgets.tawk.XTawkMessenger', array(
 *     'source'=>'https://embed.tawk.to/123456789/default',
 *     'visible'=>Yii::app()->user->isGuest,
 *     'onRoutes'=>array(
 *         'site'=>array('*'),
 *         'page'=>array('index','search','view'),
 *         'shop/product'=>array('index','search','view')
 *     ),
 *     'onHours'=>array(
 *         'Mon' => array('09:00' => '17:00'),
 *         'Tue' => array('09:00' => '17:00'),
 *         'Wed' => array('09:00' => '17:00'),
 *         'Thu' => array('09:00' => '17:00'),
 *         'Fri' => array('09:00' => '17:00'),
 *         'Sat' => array('00:00' => '00:00'),
 *         'Sun' => array('00:00' => '00:00')
 *     ),
 *     'exceptDays'=>array(
 *         date('d.m', easter_date(date('Y'))),
 *         date('d.m', strtotime("-2 day", easter_date(date('Y')))),
 *         date('d.m', strtotime("+49 day", easter_date(date('Y')))),
 *         '01.01',
 *         '24.02',
 *         '01.05',
 *         '23.06',
 *         '24.06',
 *         '20.08',
 *         '24.12',
 *         '25.12',
 *         '26.12'
 *     )
 * ));
 * </pre>
 *
 * @link https://www.tawk.to/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XTawkMessenger extends CWidget
{
	/**
	 * @var string $source the source for tawk javascript.
	 */
	public $source;
	/**
	 * @var array $visitor the list of user data for tawk API
	 */
	public $visitor=array();
	/**
	 * @var boolean $visible whether the widget is visible. Defaults to true.
	 */
	public $visible=true;
	/**
	 * @var array $onRoutes the pattern of routes that widget must be visible for.
	 * Please refer to {@link XTawkMessenger::checkRoute} on how to specify the value of this property.
	 * Note that by default the widget is visible for all routes.
	 */
	public $onRoutes=array();
	/**
	 * @var array $onHours the list of hours that widget must be visible for.
	 * Please refer to {@link XTawkMessenger::checkTime} on how to specify the value of this property.
	 * Note that by default the widget is visible for all time.
	 */
	public $onHours=array();
	/**
	 * @var array $exceptDays the list of (holi)days that widget must NOT be visible for.
	 * Please refer to {@link XTawkMessenger::checkDay} on how to specify the value of this property.
	 * Note that by default the widget is visible for all days.
	 */
	public $exceptDays=array();

	/**
	 * Init widget
	 */
	public function init()
	{
		if (!$this->source)
			throw new CException('"Source" property must not be empty!');
	}

	/**
	 * Run widget
	 */
	public function run()
	{
		// check visibility
		if(!$this->visible)
			return;

		if($this->onRoutes!==array() && !$this->checkRoute($this->onRoutes))
			return;

		if($this->onHours!==array() && !$this->checkTime($this->onHours))
			return;

		if($this->exceptDays!==array() && $this->checkDay($this->exceptDays))
			return;

		// define visitor
		$tawkApiVisitor=$this->visitor!==array() ? 'Tawk_API.visitor='.CJavaScript::encode($this->visitor).';' : null;

		// prepare widget code
		$script =
<<<SCRIPT
	<!--Start of Tawk.to Script-->
	var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
	{$tawkApiVisitor}
	(function(){
	var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
	s1.async=true;
	s1.src='{$this->source}';
	s1.charset='UTF-8';
	s1.setAttribute('crossorigin','*');
	s0.parentNode.insertBefore(s1,s0);
	})();
	<!--End of Tawk.to Script-->
SCRIPT;

		// register widget code
		Yii::app()->clientScript->registerScript(__CLASS__, $script, CClientScript::POS_END);
	}

	/**
	 * Check whether the current route is within given pattern of routes
	 *
	 * The following is an example of pattern:
	 * <pre>
	 * array (
	 *   'site'=>array('*'), // all actions
	 *   'page'=>array('index','search','view'),
	 *   'shop/product'=>array('index','search','view')
	 * )
	 * </pre>
	 *
	 * @param array $pattern the pattern of routes.
	 * @return boolean whether the current route matches given pattern
	 */
	protected function checkRoute($pattern)
	{
		$route=$this->controller->getRoute();
		foreach($pattern as $controller=>$actions)
		{
			foreach($actions as $action)
			{
				if($action=='*' && $this->controller->uniqueID==$controller)
				   return true;
				elseif($route==$controller.'/'.$action)
				   return true;
			}
		}
		return false;
	}

	/**
	 * Check whether the current time is within given hours
	 *
	 * The following is an example of hours:
	 * <pre>
	 * array(
	 *   'Mon' => array('09:00' => '17:00','19:00' => '21:00'),
	 *   'Tue' => array('09:00' => '17:00'),
	 *   'Wed' => array('09:00' => '17:00'),
	 *   'Thu' => array('09:00' => '17:00'),
	 *   'Fri' => array('09:00' => '17:00'),
	 *   'Sat' => array('00:00' => '00:00'),
	 *   'Sun' => array('00:00' => '00:00')
	 * )
	 * </pre>
	 *
	 * @param array $hours the list of hours.
	 * @return boolean whether the current time is within given hours
	 */
	public function checkTime($hours)
	{
		$timestamp=time();
		$r=false;

		// get current time object
		$dt=new DateTime();
		$currentTime=$dt->setTimestamp($timestamp);

		// loop through time ranges for current day
		foreach($hours[date('D',$timestamp)] as $startTime=>$endTime)
		{
			// create time objects from start/end times
			$startTime=DateTime::createFromFormat('H:i',$startTime);
			$endTime=DateTime::createFromFormat('H:i',$endTime);

			// check if current time is within a range
			if(($startTime<$currentTime)&&($currentTime<$endTime))
			{
				$r=true;
				break;
			}
		}

		return $r;
	}

	/**
	 * Check whether the current day is within given days
	 *
	 * The following is an example of days:
	 * <pre>
	 * array(
	 *   '01.01',
	 *   '24.02',
	 *   '01.05',
	 *   '23.06',
	 *   '24.06',
	 *   '20.08',
	 *   '24.12',
	 *   '25.12',
	 *   '26.12',
	 *   date('d.m', easter_date(date('Y')))
	 * )
	 * </pre>
	 *
	 * @param array $days the list of days.
	 * @return boolean whether the current day is within given days
	 */
	public function checkDay($days)
	{
		$currentDay=date('d.m', time());
		return in_array($currentDay, $days);
	}
}