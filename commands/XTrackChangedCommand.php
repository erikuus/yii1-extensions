<?php
/**
 * XTrackChangedCommand class file.
 *
 * This command allows you to track down files in application directory
 * that have changed after given point in time.
 *
 * To use this command, first map it in protected/config/console.php as follows:
 * return array(
 *     'commandMap' => array(
 *         'track'=>array(
 *             'class'=>'ext.commands.XTrackChangedCommand',
 *             'webroot'=>'path/to/your/application/webroot/'
 *         )
 *     )
 * );
 *
 * Now, under protected directory, you can run this command:
 * yiic track 2011-11-28
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XTrackChangedCommand extends CConsoleCommand
{
	private $date;
	public $webroot;

	public function getHelp()
	{
		$out = ".\n\n";
		return $out.parent::getHelp();
	}

	public function run ($args)
	{
		if(isset($args[0]))
		{
			$this->date=date_parse($args[0]);
			var_export($this->rscandir($this->webroot));
		}
		else
			echo "No date submitted!.\n";
	}

	private function rscandir($base='', &$data=array())
	{
		$array = array_diff(scandir($base), array('.', '..'));
		foreach($array as $value)
		{
			if (is_dir($base.$value))
			{
				if (!strstr($base.$value, '/assets') &&
					!strstr($base.$value, '/runtime') &&
					!strstr($base.$value, '/upload') &&
					!strstr($base.$value, '/.cache') &&
					!strstr($base.$value, '/.settings') &&
					!strstr($base.$value, '/.svn')
				)
					$data = $this->rscandir($base.$value.'/', $data);
			}
			elseif (is_file($base.$value))
			{
				if (filemtime($base.$value) > mktime(0, 0, 0, $this->date['month'], $this->date['day'], $this->date['year']))
					$data[] = $base.$value;
			}
		}
		return $data;
	}
}