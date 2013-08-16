<?php
/**
 * XChart class file
 *
 * XChart is a wrapper for a xCharts (http://tenxer.github.io/xcharts/)
 *
 * xCharts is a JavaScript library for building beautiful and custom data-driven chart visualizations for the web using D3.js.
 * Using HTML, CSS, and SVG, xCharts are designed to be dynamic, fluid, and open to integrations and customization.
 *
 * Basic example:
 *
 * <pre>
 * $this->widget('ext.widgets.chart.XChart', array(
 *     'id'=>'activityChart',
 *     'type'=>'line-dotted',
 *     'data'=> array(
 *         'xScale'=>'time',
 *         'yScale'=>'linear',
 *         'main'=>array(
 *             array(
 *                 'className'=>'.line1',
 *                 'data'=> array(
 *                     array(
 *                         'x'=>'2013-03-02',
 *                         'y'=>10
 *                     ),
 *                     array(
 *                         'x'=>'2013-03-03',
 *                         'y'=>70
 *                     ),
 *                     array(
 *                         'x'=>'2013-03-04',
 *                         'y'=>100
 *                     ),
 *                 )
 *             ),
 *             array(
 *                 'className'=>'.line2',
 *                 'data'=> array(
 *                     array(
 *                         'x'=>'2013-03-02',
 *                         'y'=>20
 *                     ),
 *                     array(
 *                         'x'=>'2013-03-03',
 *                         'y'=>50
 *                     ),
 *                     array(
 *                         'x'=>'2013-03-04',
 *                         'y'=>80
 *                     ),
 *                 )
 *             ),
 *         ),
 *     ),
 *     'options'=>array(
 *         'dataFormatX'=>"js:function (x) {
 *             return d3.time.format('%Y-%m-%d').parse(x);
 *         }",
 *         'tickFormatX'=>"js:function (x) {
 *             return d3.time.format('%b %_d')(x).toUpperCase();
 *         }",
 *         'axisPaddingTop'=>13,
 *         'axisPaddingLeft'=>48,
 *         'tickHintX'=>7
 *     ),
 *     'htmlOptions'=>array(
 *         'style'=>'width:100%; height:500px;'
 *     )
 * ));
 * </pre>
 *
 * Complex example (data from model, ajax update chart data):
 *
 * Widget:
 *
 * <pre>
 * $this->widget('ext.widgets.chart.XChart', array(
 *     'id'=>'contestActivityChart',
 *     'cssFile'=>false,
 *     'type'=>'line-dotted',
 *     'data'=> array(
 *         'xScale'=>'time',
 *         'yScale'=>'linear',
 *         'main'=>array(
 *             array(
 *                 'className'=>'.photoViews',
 *                 'data'=>Contest::model()->getContestViewsGraph($model->id),
 *             ),
 *         ),
 *     ),
 *     'htmlOptions'=>array(
 *         'style'=>'width:100%; height:500px;'
 *     )
 * ));
 * </pre>
 *
 * Links to change graph:
 *
 * <pre>
 * <ul class="contest-stats">
 *     <li class="active" data-graph="contestViews">
 *         <a href="#">
 *             <span class="stat-label">Contest Views</span>
 *         </a>
 *     </li>
 *     <li data-graph="photoViews">
 *         <a href="#">
 *             <span class="stat-label">Photo Views</span>
 *         </a>
 *     </li>
 * </ul>
 * </pre>
 *
 * Client script to get and set data:
 *
 * <pre>
 * Yii::app()->clientScript->registerScript('xChart', "
 *     $('.contest-stats li a').on('click', function(e){
 *         e.preventDefault();
 *         var graphName = $(this).parents('li').data('graph');
 *         $.ajax({
 *             url:'".$this->createUrl('request/getGraph',array('contestId'=>$model->id))."',
 *             dataType:'json',
 *             data:{
 *                 'graphName':graphName
 *             },
 *             cache:false,
 *             success:function(data){
 *                 contestActivityChart.setData(data);
 *             }
 *         });
 *     });
 * ", CClientScript::POS_READY);
 * </pre>
 *
 * Request controller:
 *
 * <pre>
 * public function actionGetGraph($graphName,$contestId=0)
 * {
 *    switch ($graphName) {
 *        case 'contestViews':
 *           $data=Contest::model()->getContestViewsGraph($contestId);
 *        break;
 *        case 'photoViews':
 *            $data=Contest::model()->getPhotoViewsGraph($contestId);
 *        break;
 *    }
 *
 *    echo CJSON::encode(array(
 *        'xScale'=>'time',
 *        'yScale'=>'linear',
 *        'main'=>array(
 *            array(
 *                'className'=>".{$graphName}",
 *                'data'=>$data,
 *            )
 *        )
 *    ));
 * }
 * </pre>
 *
 * Model:
 *
 * <pre>
 * public function getContestPhotoViewsGraph($contestId)
 * {
 *     $rows=Yii::app()->db->createCommand("
 *         SELECT date(Created) AS x, COUNT(*) AS y
 *         FROM a_photoview
 *         WHERE ContestID={$contestId}
 *         GROUP BY x
 *         ORDER BY x
 *     ")->queryAll();
 *
 *     $data=array();
 *     foreach ($rows as $row) {
 *         $data[]=array(
 *             'x'=>$row['x'],
 *             'y'=>$row['y']
 *         );
 *     }
 *     return $data;
 * }
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XChart extends CWidget
{
	/**
	 * @var string chart visual type. Valid Options: 'bar', 'cumulative', 'line', 'line-dotted'.
	 */
	public $type;

	/**
	 * @var array the chart input data. Valid elements include the following:
	 *
	 * xScale - Scale type to use along the x-axis (horizontal). Valid Options: 'ordinal', 'linear', 'time', 'exponential'
	 * yScale - Scale type to use along the y-axis (vertical). Valid Options: 'ordinal', 'linear', 'time', 'exponential'
	 * xMin - Minimum allowed value on the xScale. If null, uses the data's min value, logically padded for aesthetics. Does not affect ordinal scales.
	 * xMax - Maximum allowed value on the xScale. If null, uses the data's max value, logically padded for aesthetics. Does not affect ordinal scales.
	 * yMin - Minimum allowed value on the yScale. If null, uses the data's min value, logically padded for aesthetics. Does not affect ordinal scales.
	 * yMax - Maximum allowed value on the yScale. If null, uses the data's max value, logically padded for aesthetics. Does not affect ordinal scales.
	 * type - The optional type to force the main data to display with. If not provided, will use the last set type, or the one provided at xChart creation time.
	 * main - An array of data sets to be drawn. Valid elements include the following:
	 *     className - A unique CSS Selector of classes to use in identifying the elements on the chart.
	 *     data - An array of objects containing the x and y values. This data will be converted to ascending sort by xCharts.
	 *
	 *  For more information see documentation: http://tenxer.github.io/xcharts/docs/#data
	 */
	public $data=array();

	/**
	 * @var array additional options that can be passed to the constructor of the js object. Valid elements include the following:
	 *
	 * mouseover - Callback behavior for a user mousing over a data point.
	 * mouseout - Callback behavior for a user mousing off a data point.
	 * click - Callback behavior for a user clicking a data point.
	 * axisPaddingTop - Amount of space between the top of the chart and the top value on the y-scale.
	 * axisPaddingRight - Amount of space between the right side of the chart and the highest value on the x-scale.
	 * axisPaddingBottom - Amount of space between the bottom of the chart and the lowest value on the y-scale.
	 * axisPaddingLeft - Amount of space between the left side of the chart and the lowest value on the x-scale.
	 * xMin - Minimum allowed value on the xScale. If null, uses the data's min value, logically padded for aesthetics. Does not affect ordinal scales. May be overrided using the setData method with the xMin data format key.
	 * xMax - Maximum allowed value on the xScale. If null, uses the data's max value, logically padded for aesthetics. Does not affect ordinal scales. May be overrided using the setData method with the xMax data format key.
	 * yMin - Minimum allowed value on the yScale. If null, uses the data's min value, logically padded for aesthetics. Does not affect ordinal scales. May be overrided using the setData method with the yMin data format key.
	 * yMax - Maximum allowed value on the yScale. If null, uses the data's max value, logically padded for aesthetics. Does not affect ordinal scales. May be overrided using the setData method with the yMax data format key.
	 * paddingTop - Amount of space from the top edge of the svg element to the beginning of the axisPaddingTop.
	 * paddingRight - Amount of space from the right edge of the svg element to the beginning of the axisPaddingRight.
	 * paddingBottom - Allows space for the x-axis scale. Controls the amount of space from the bottom edge of the svg element to the beginning of the axisPaddingBottom.
	 * paddingLeft - Allows space for the y-axis scale. Amount of space from the left edge of the svg element to the beginning of the axisPaddingLeft.
	 * tickHintX - The amount of ticks that you would like to have displayed on the x-axis. Note: this is merely a guide and your results will likely vary.
	 * tickWidthX - The estimated width of tick labels displayed on the x-axis (This option is not in the original code. It was added by Erik Uus)
	 * tickFormatX - Provide alternate formatting for the x-axis tick labels.
	 * tickHintY - The amount of ticks that you would like to have displayed on the y-axis. Note: this is merely a guide and your results will likely vary.
	 * tickFormatY - Provide alternate formatting for the y-axis tick labels.
	 * dataFormatX - A method to pre-format the input data for the x-axis before attempting to compute a scale or draw.
	 * dataFormatY - A method to pre-format the input data for the y-axis before attempting to compute a scale or draw.
	 * unsupported - A callback method that will be invoked if SVG is not supported in the viewer's browser.
	 * noDataMainOnly
	 * empty - A callback method invoked when the data set provided was empty and there is nothing to draw.
	 * notempty - The opposite of empty. Invoked if the data set provided was not empty.
	 * timing - The amount of time, in milliseconds, to transition during draw/update.
	 * interpolation - Line interpolation to use when drawing lines. See d3.js's line.interpolate for more information.
	 *
	 * For more information see documentation:  http://tenxer.github.io/xcharts/docs/#options
	 */
	public $options=array();

	/**
	 * @var array additional html options for container tag
	 */
	public $htmlOptions=array();

	/**
	 * @var mixed the CSS file used for the widget. Defaults to null, meaning
	 * using the default CSS file included together with the widget.
	 * If false, no CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 */
	public $cssFile;

	public function init()
	{
		if (!$this->type)
			throw new CException('"Type" property must not be empty');

		if ($this->data===array())
			throw new CException('"Data" property must not be empty');
	}

	public function run()
	{
		$this->registerClientScript();

		if(isset($this->htmlOptions['id']))
			$this->id=$this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$this->id;

		echo CHtml::tag('figure', $this->htmlOptions, '');
	}

	/**
	 * Publish and register necessary client scripts.
	 */
	protected function registerClientScript()
	{
		$cs=Yii::app()->clientScript;
		$assets=Yii::app()->assetManager->publish(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');

		// register css file
		if($this->cssFile===null)
		{
			if (YII_DEBUG)
				$cs->registerCssFile($assets. '/xcharts.css');
			else
				$cs->registerCssFile($assets. '/xcharts.min.css');
		}
		else if($this->cssFile!==false)
			$cs->registerCssFile($this->cssFile);

		// register d3 script
		$cs->registerScriptFile($assets. '/d3.min.js');

		// register xcharts script
		if (YII_DEBUG)
			$cs->registerScriptFile($assets. '/xcharts.js');
		else
			$cs->registerScriptFile($assets. '/xcharts.min.js');

		// prepare options
		$options=CJavaScript::encode($this->options);

		// prepare data
		$data=CJavaScript::encode($this->data);

		// register inline script
		$script="var {$this->id}=new xChart('{$this->type}', {$data}, '#{$this->id}', {$options});\n";

		$cs->registerScript(__CLASS__ . '#' . $this->id, $script, CClientScript::POS_END);
	}
}
