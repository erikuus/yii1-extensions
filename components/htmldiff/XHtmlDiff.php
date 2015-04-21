<?php
/**
 * XHtmlDiff class file.
 *
 * XHtmlDiff makes use of PHP port of myobie's htmldiff for comparing two HTML files/snippets and highlighting the differences using simple HTML
 *
 * The following shows how to use the component.
 *
 * <pre>
 * Yii::import('ext.components.htmldiff.XHtmlDiff');
 * $diff=XHtmlDiff::compare(file_get_contents($path), $content);
 * </pre>
 *
 * @link ruby implementation https://github.com/myobie/htmldiff
 * @link php implementation https://github.com/rashid2538/php-htmldiff
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(__FILE__).'/vendor/HtmlDiff.php';

class XHtmlDiff extends CComponent
{
	public static function compare($html1, $html2)
	{
		$diff = new HtmlDiff($html1, $html2);
		$diff->build();
		return $diff->getDifference();
	}
}