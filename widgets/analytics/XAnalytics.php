<?php
/**
 * XAnalytics class file
 *
 * Widget to implement a Piwik Analytics
 *
 * Example of usage:
 *
 * $this->widget('ext.widgets.analytics.XAnalytics', array(
 *     'visible'=>true,
 *     'tracker'=>'12345-67-89-1112-131415',
 * ));
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XAnalytics extends CWidget
{
	/**
	 * @var string the tracker code as given in Piwik Analytics dashboard.
	 */
	public $tracker;
	/**
	 * @var boolean whether the widget is visible. Defaults to true.
	 */
	public $visible=true;

	public function run()
	{
		if(!$this->visible || !$this->tracker)
			return;

		// register js code
		$code =
<<<SCRIPT
(function(window, document, dataLayerName, id) {
window[dataLayerName]=window[dataLayerName]||[],window[dataLayerName].push({start:(new Date).getTime(),event:"stg.start"});var scripts=document.getElementsByTagName('script')[0],tags=document.createElement('script');
function stgCreateCookie(a,b,c){var d="";if(c){var e=new Date;e.setTime(e.getTime()+24*c*60*60*1e3),d="; expires="+e.toUTCString();f="; SameSite=Strict"}document.cookie=a+"="+b+d+f+"; path=/"}
var isStgDebug=(window.location.href.match("stg_debug")||document.cookie.match("stg_debug"))&&!window.location.href.match("stg_disable_debug");stgCreateCookie("stg_debug",isStgDebug?1:"",isStgDebug?14:-1);
var qP=[];dataLayerName!=="dataLayer"&&qP.push("data_layer_name="+dataLayerName),isStgDebug&&qP.push("stg_debug");var qPString=qP.length>0?("?"+qP.join("&")):"";
tags.async=!0,tags.src="https://ra-2.containers.piwik.pro/"+id+".js"+qPString,scripts.parentNode.insertBefore(tags,scripts);
!function(a,n,i){a[n]=a[n]||{};for(var c=0;c<i.length;c++)!function(i){a[n][i]=a[n][i]||{},a[n][i].api=a[n][i].api||function(){var a=[].slice.call(arguments,0);"string"==typeof a[0]&&window[dataLayerName].push({event:n+"."+i+":"+a[0],parameters:[].slice.call(arguments,1)})}}(i[c])}(window,"ppms",["tm","cm"]);
})(window, document, 'dataLayer', '{$this->tracker}');
SCRIPT;

		Yii::app()->clientScript->registerScript(__CLASS__, $code, CClientScript::POS_BEGIN);
	}
}