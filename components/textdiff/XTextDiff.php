<?php
/**
 * XTextDiff class file.
 *
 * XTextDiff makes use of PEAR TextTiff engine for performing and rendering text diffs
 *
 * The following shows how to use the component.
 *
 * <pre>
 * Yii::import('ext.components.textdiff.XTextDiff');
 * $diff=XTextDiff::compare(file_get_contents($path), $content);
 * </pre>
 *
 * @link https://pear.php.net/package/Text_Diff
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

Yii::import('gii.components.Pear.*');
require_once 'Text/Diff.php';
require_once 'Text/Diff/Renderer.php';
require_once 'Text/Diff/Renderer/inline.php';

class XTextDiff extends CComponent
{
	public static function compare($lines1, $lines2)
	{
		if(is_string($lines1))
			$lines1=explode("\n",$lines1);
		if(is_string($lines2))
			$lines2=explode("\n",$lines2);
		$diff = new Text_Diff('auto', array($lines1, $lines2));
		$renderer = new Text_Diff_Renderer_inline();
		return $renderer->render($diff);
	}
}