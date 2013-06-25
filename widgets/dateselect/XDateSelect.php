<?php
/**
 * XDateSelect is a widget that creates date dropdowns.
 * It can display any or all of year, month, and day.
 * Based on the Smarty function {html_select_date}
 * http://www.smarty.net/manual/en/language.function.html.select.date.php
 *
 * Example:
 *
 * Widget:
 * <pre>
 * $this->widget('ext.widgets.dateselect.XDateSelect', array(
 *     'model'=>$model,
 *     'attribute'=>'DateOfBirth',
 *     'reverseYears'=>true,
 *     'fieldOrder'=>'DMY',
 *     'startYear'=>1910,
 *     'endYear'=>date("Y",time()),
 *     'dayTemplate'=>'<div>{select}</div>',
 *     'monthTemplate'=>'<div>{select}</div>',
 *     'yearTemplate'=>'<div style="float: left;">{select}</div>',
 * ));
 * </pre>
 *
 * Controller:
 * <pre>
 * public function actionCreate()
 * {
 *     $model=new Account('createAccount');
 *
 *     if(isset($_POST['Account']))
 *     {
 *         $model->attributes=$_POST['Account'];
 *         Yii::import('ext.widgets.dateselect.XDateSelect');
 *         XDateSelect::sanitize($model, 'DateOfBirth');
 *
 *         if($model->save())
 *             $this->redirect(array('admin'));
 *     }
 *
 *     $this->render('create',array(
 *         'model'=>$model,
 *     ));
 * }
 * </pre>
 *
 * @author Vladimir Papaev <kosenka@gmail.com>
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 0.3
 */
class XDateSelect extends CInputWidget
{
	// What to prefix the var name with
	public $prefix;

	// What date/time to use
	public $time;

	// The first year in the dropdown, either year number, or relative to current year (+/- N)
	public $startYear;

	// The last year in the dropdown, either year number, or relative to current year (+/- N)
	public $endYear;

	// Whether to display days or not
	public $displayDays;

	// Whether to display months or not
	public $displayMonths;

	// Whether to display years or not
	public $displayYears;

	// What format the month should be in (strftime)
	public $monthFormat;

	// strftime() format of the month values, default is %m for month numbers.
	public $monthValueFormat;

	// What format the day output should be in (sprintf)
	public $dayFormat;

	// What format the day value should be in (sprintf)
	public $dayValueFormat;

	// Whether or not to display the year as text
	public $yearAsText;

	// Display years in reverse order
	public $reverseYears;

	// If a name is given, the select boxes will be drawn such that the results will be returned to PHP in the form of name[Day], name[Year], name[Month]
	public $fieldArray;

	// Adds size attribute to select tag if given
	public $daySize;

	// Adds size attribute to select tag if given
	public $monthSize;

	// Adds size attribute to select tag if given
	public $yearSize;

	// Adds extra attributes to all select/input tags if given
	public $allExtra;

	// Adds extra attributes to select/input tags if given
	public $dayExtra;

	// Adds extra attributes to select/input tags if given
	public $monthExtra;

	// Adds extra attributes to select/input tags if given
	public $yearExtra;

	// The order in which to display the fields
	public $fieldOrder;

	// String printed between different fields
	public $fieldSeparator;

	//If supplied then the first element of the year's select-box has this value as it's label and "" as it's value.
	//This is useful to make the select-box read "Please select a year" for example.
	//Note that you can use values like "-MM-DD" as time-attribute to indicate an unselected year.
	public $dayEmpty;

	//If supplied then the first element of the month's select-box has this value as it's label and "" as it's value.
	//Note that you can use values like "YYYY--DD" as time-attribute to indicate an unselected month.
	public $monthEmpty;

	//If supplied then the first element of the day's select-box has this value as it's label and "" as it's value.
	//Note that you can use values like "YYYY-MM-" as time-attribute to indicate an unselected day.
	public $yearEmpty;

	// Specifies how day select element is rendered
	public $dayTemplate='{select}';

	// Specifies how month select element is rendered
	public $monthTemplate='{select}';

	// Specifies how year select element is rendered
	public $yearTemplate='{select}';

	private $monthNamesLocale;

	public function init()
	{
		parent::init();

		list($name)=$this->resolveNameID();

		$this->monthNamesLocale=Yii::app()->getLocale()->getMonthNames('wide',true);
		$this->time=$this->model[$this->attribute];

		if(!isset($this->prefix) or empty($this->prefix))
			$this->prefix="";
		if(!isset($this->startYear) or empty($this->startYear))
			$this->startYear=strftime("%Y");
		if(!isset($this->endYear) or empty($this->endYear))
			$this->endYear=$this->startYear;
		if(!isset($this->displayDays))
			$this->displayDays=true;
		if(!isset($this->displayMonths))
			$this->displayMonths=true;
		if(!isset($this->displayYears))
			$this->displayYears=true;
		if(!isset($this->monthFormat) or empty($this->monthFormat))
			$this->monthFormat="%B";
		/* Write months as numbers by default  GL */
		if(!isset($this->monthValueFormat) or empty($this->monthValueFormat))
			$this->monthValueFormat="%m";
		if(!isset($this->dayFormat) or empty($this->dayFormat))
			$this->dayFormat="%02d";
		/* Write day values using this format MB */
		if(!isset($this->dayValueFormat) or empty($this->dayValueFormat))
			$this->dayValueFormat="%d";
		if(!isset($this->yearAsText))
			$this->yearAsText=false;
		/* Display years in reverse order? Ie. 2000,1999,.... */
		if(!isset($this->reverseYears))
			$this->reverseYears=false;
		/* Should the select boxes be part of an array when returned from PHP?
		 e.g. setting it to "birthday", would create "birthday[Day]",
		 "birthday[Month]" & "birthday[Year]". Can be combined with prefix */
		if(!isset($this->fieldArray))
			$this->fieldArray=$name;
		/* <select size>'s of the different <select> tags.
		 If not set, uses default dropdown. */
		if(!isset($this->daySize))
			$this->daySize=null;
		if(!isset($this->monthSize))
			$this->monthSize=null;
		if(!isset($this->yearSize))
			$this->yearSize=null;
		/* Unparsed attributes common to *ALL* the <select>/<input> tags.
		 An example might be in the template: allExtra ='class ="foo"'. */
		if(!isset($this->allExtra))
			$this->allExtra=null;

		/* Separate attributes for the tags. */
		if(!isset($this->dayExtra))
			$this->dayExtra=null;
		if(!isset($this->monthExtra))
			$this->monthExtra=null;
		if(!isset($this->yearExtra))
			$this->yearExtra=null;

		/* Order in which to display the fields.
		 "D" -> day, "M" -> month, "Y" -> year. */
		if(!isset($this->fieldOrder) or empty($this->fieldOrder))
			$this->fieldOrder='MDY';
		/* String printed between the different fields. */
		if(!isset($this->fieldSeparator))
			$this->fieldSeparator="\n";
		if(!isset($this->time))
			$this->time=time();
		if(!isset($this->dayEmpty))
			$this->dayEmpty=null;
		if(!isset($this->monthEmpty))
			$this->monthEmpty=null;
		if(!isset($this->yearEmpty))
			$this->yearEmpty=null;
	}

	public function run()
	{
		if(preg_match('!^-\d+$!',$this->time))
		{
			// negative timestamp, use date()
			$this->time=date('Y-m-d',$this->time);
		}

		// If $time is not in format yyyy-mm-dd
		if(preg_match('/^(\d{0,4}-\d{0,2}-\d{0,2})/',$this->time,$found))
		{
			$this->time=$found[1];
		}
		else
		{
			// use makeTimestamp to get an unix timestamp and
			// strftime to make yyyy-mm-dd
			$this->time=strftime('%Y-%m-%d',$this->makeTimestamp($this->time));
		}

		// Now split this in pieces, which later can be used to set the select
		$this->time=explode("-",$this->time);

		// make syntax "+N" or "-N" work with startYear and endYear
		if(preg_match('!^(\+|\-)\s*(\d+)$!',$this->endYear,$match))
		{
			if($match[1]=='+')
			{
				$this->endYear=strftime('%Y')+$match[2];
			}
			else
			{
				$this->endYear=strftime('%Y')-$match[2];
			}
		}

		if(preg_match('!^(\+|\-)\s*(\d+)$!',$this->startYear,$match))
		{
			if($match[1]=='+')
			{
				$this->startYear=strftime('%Y')+$match[2];
			}
			else
			{
				$this->startYear=strftime('%Y')-$match[2];
			}
		}

		if(strlen($this->time[0])>0)
		{
			if($this->startYear>$this->time[0]&&!isset($this->startYear))
			{
				// force start year to include given date if not explicitly set
				$this->startYear=$this->time[0];
			}
			if($this->endYear<$this->time[0]&&!isset($this->endYear))
			{
				// force end year to include given date if not explicitly set
				$this->endYear=$this->time[0];
			}
		}

		$this->fieldOrder=strtoupper($this->fieldOrder);

		$htmlResult=$monthResult=$dayResult=$yearResult="";

		if($this->displayMonths)
		{
			$month_names=array();
			$month_values=array();
			if(isset($this->monthEmpty))
			{
				$month_names['']=$this->monthEmpty;
				$month_values['']='';
			}

			for($i=1;$i<=12;$i++)
			{
				$month_names[$i]=$this->monthNamesLocale[$i]; //strftime($this->monthFormat, mktime(0, 0, 0, $i, 1, 2000));
				$month_values[$i]=strftime($this->monthValueFormat,mktime(0,0,0,$i,1,2000));
			}

			$monthResult.='<select name=';
			if(null!==$this->fieldArray)
			{
				$monthResult.='"'.$this->fieldArray.'['.$this->prefix.'Month]"';
			}
			else
			{
				$monthResult.='"'.$this->prefix.'Month"';
			}

			if(null!==$this->monthSize)
			{
				$monthResult.=' size="'.$this->monthSize.'"';
			}

			if(null!==$this->monthExtra)
			{
				$monthResult.=' '.$this->monthExtra;
			}

			if(null!==$this->allExtra)
			{
				$monthResult.=' '.$this->allExtra;
			}

			$monthResult.='>'."\n";

			$monthResult.=$this->htmlOptions(array('output'=>$month_names,'values'=>$month_values,'selected'=>$this->monthEmpty==='' && !$this->model[$this->attribute] ? null : strftime($this->monthValueFormat,mktime(0,0,0,(int)$this->time[1],1,2000)),'print_result'=>false));
			$monthResult.='</select>';
		}

		if($this->displayDays)
		{
			$days=array();
			if(isset($this->dayEmpty))
			{
				$days['']=$this->dayEmpty;
				$day_values['']='';
			}
			for($i=1;$i<=31;$i++)
			{
				$days[]=sprintf($this->dayFormat,$i);
				$day_values[]=sprintf($this->dayValueFormat,$i);
			}

			$dayResult.='<select name=';
			if(null!==$this->fieldArray)
			{
				$dayResult.='"'.$this->fieldArray.'['.$this->prefix.'Day]"';
			}
			else
			{
				$dayResult.='"'.$this->prefix.'Day"';
			}

			if(null!==$this->daySize)
			{
				$dayResult.=' size="'.$this->daySize.'"';
			}

			if(null!==$this->allExtra)
			{
				$dayResult.=' '.$this->allExtra;
			}

			if(null!==$this->dayExtra)
			{
				$dayResult.=' '.$this->dayExtra;
			}
			$dayResult.='>'."\n";
			$dayResult.=$this->htmlOptions(array('output'=>$days,'values'=>$day_values,'selected'=>$this->monthEmpty==='' && !$this->model[$this->attribute] ?  null : $this->time[2],'print_result'=>false));
			$dayResult.='</select>';
		}

		if($this->displayYears)
		{
			if(null!==$this->fieldArray)
			{
				$year_name=$this->fieldArray.'['.$this->prefix.'Year]';
			}
			else
			{
				$year_name=$this->prefix.'Year';
			}

			if($this->yearAsText)
			{
				$yearResult.='<input type="text" name="'.$year_name.'" value="'.$this->time[0].'" size="4" maxlength="4"';
				if(null!==$this->allExtra)
				{
					$yearResult.=' '.$this->allExtra;
				}
				if(null!==$this->yearExtra)
				{
					$yearResult.=' '.$this->yearExtra;
				}
				$yearResult.=' />';
			}
			else
			{
				$years=range((int)$this->startYear,(int)$this->endYear);
				if($this->reverseYears)
				{
					rsort($years,SORT_NUMERIC);
				}
				else
				{
					sort($years,SORT_NUMERIC);
				}

				$yearvals=$years;
				if(isset($this->yearEmpty))
				{
					array_unshift($years,$this->yearEmpty);
					array_unshift($yearvals,'');
				}

				$yearResult.='<select name="'.$year_name.'"';
				if(null!==$this->yearSize)
				{
					$yearResult.=' size="'.$this->yearSize.'"';
				}

				if(null!==$this->allExtra)
				{
					$yearResult.=' '.$this->allExtra;
				}

				if(null!==$this->yearExtra)
				{
					$yearResult.=' '.$this->yearExtra;
				}

				$yearResult.='>'."\n";
				$yearResult.=$this->htmlOptions(array('output'=>$years,'values'=>$yearvals,'selected'=>$this->yearEmpty==='' && !$this->model[$this->attribute] ?  null : $this->time[0], 'print_result'=>false));
				$yearResult.='</select>';
			}
		}

		// Loop thru the fieldOrder field
		for($i=0;$i<=2;$i++)
		{
			$c=substr($this->fieldOrder,$i,1);
			switch($c)
			{
				case 'D':
					$htmlResult.=strtr($this->dayTemplate,array('{select}'=>$dayResult));
					break;
				case 'M':
					$htmlResult.=strtr($this->monthTemplate,array('{select}'=>$monthResult));
					break;
				case 'Y':
					$htmlResult.=strtr($this->yearTemplate,array('{select}'=>$yearResult));
					break;
			}
			// Add the field seperator
			if($i!=2)
			{
				$htmlResult.=$this->fieldSeparator;
			}
		}

		//myExt::dump($htmlResult);
		echo $htmlResult;
	}

	/**
	 * The actual handler attached to the model's onBeforeValidate event.
	 */
	public static function conversionHandler($event,$attribute)
	{
		$value=$event->sender->$attribute;
		if(!$value || is_string($value))
			return true;

		if(is_array($value) && array_filter($value)===array())
			$event->sender->$attribute=null;
		else
			$event->sender->$attribute=strtr('Year-Month-Day',(array)$value);

		return true;
	}

	/**
	 * Attaches an event handler to the model's onBeforeValidate event.
	 * This handles merging values returned by the dropdown lists back into a single string.
	 */
	public static function sanitize($model,$attributes)
	{
		$model->attachEventHandler('onBeforeValidate',create_function('$event','return '.__CLASS__."::conversionHandler(\$event, '$attributes');"));
	}

	protected function makeTimestamp($string)
	{
		if(empty($string))
		{
			// use "now":
			$time=time();
		}
		elseif(preg_match('/^\d{14}$/',$string))
		{
			// it is mysql timestamp format of YYYYMMDDHHMMSS?
			$time=mktime(substr($string,8,2),substr($string,10,2),substr($string,12,2),substr($string,4,2),substr($string,6,2),substr($string,0,4));
		}
		elseif(is_numeric($string))
		{
			// it is a numeric string, we handle it as timestamp
			$time=(int)$string;
		}
		else
		{
			// strtotime should handle it
			$time=strtotime($string);
			if($time==-1||$time===false)
			{
				// strtotime() was not able to parse $string, use "now":
				$time=time();
			}
		}
		return $time;
	}

	protected function htmlOptions($params)
	{
		$name=null;
		$values=null;
		$options=null;
		$selected=array();
		$output=null;

		$extra='';

		foreach($params as $key=>$val)
		{
			switch($key)
			{
				case 'name':
					${$key}=(string)$val;
					break;
				case 'options':
					${$key}=(array)$val;
					break;

				case 'values':
				case 'output':
					${$key}=array_values((array)$val);
					break;

				case 'selected':
					${$key}=array_map('strval',array_values((array)$val));
					break;

				default:
					if(!is_array($val))
					{
						//$extra .= ' '.$key.'="'.quicky_function_escape_special_chars($val).'"';
						$extra.=' '.$key.'="'.$val.'"';
					}
					else
					{
						throw new CException("htmlOptions: extra attribute ".$key." cannot be an array");
					}
					break;
			}
		}

		if(!isset($options)&&!isset($values))
			return ''; /* raise error here? */

		$htmlResult='';

		if(isset($options))
		{
			foreach($options as $key=>$val)
				$htmlResult.=$this->htmlOptionsOptoutput($key,$val,$selected);
		}
		else
		{
			foreach($values as $i=>$key)
			{
				$val=isset($output[$i]) ? $output[$i] : '';
				$htmlResult.=$this->htmlOptionsOptoutput($key,$val,$selected);
			}
		}

		if(!empty($name))
		{
			$htmlResult='<select name="'.$name.'"'.$extra.'>'."\n".$htmlResult.'</select>'."\n";
		}

		return $htmlResult;
	}

	protected function htmlOptionsOptoutput($key,$value,$selected)
	{
		if(!is_array($value))
		{
			//$htmlResult = '<option label="' . quicky_function_escape_special_chars($value) . '" value="' .quicky_function_escape_special_chars($key) . '"';
			$htmlResult='<option label="'.$value.'" value="'.$key.'"';
			if(in_array((string)$key,$selected))
				$htmlResult.=' selected="selected"';

			//$htmlResult .= '>' . quicky_function_escape_special_chars($value) . '</option>' . "\n";
			$htmlResult.='>'.$value.'</option>'."\n";
		}
		else
		{
			$htmlResult=$this->htmlOptionsOptgroup($key,$value,$selected);
		}
		return $htmlResult;
	}

	protected function htmlOptionsOptgroup($key,$values,$selected)
	{
		//$optgroup_html = '<optgroup label="' . quicky_function_escape_special_chars($key) . '">' . "\n";
		$optgroup_html='<optgroup label="'.$key.'">'."\n";
		foreach($values as $key=>$value)
		{
			$optgroup_html.=$this->htmlOptionsOptoutput($key,$value,$selected);
		}
		$optgroup_html.="</optgroup>\n";
		return $optgroup_html;
	}
}