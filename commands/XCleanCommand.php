<?php
/**
 * XCleanCommand class file.
 *
 * This command allows you to clean up various temporary data Yii and an application are generating.
 *
 * To use this command, first map it in protected/config/console.php as follows:
 * return array(
 *     'commandMap' => array(
 *         'clean'=>array(
 *             'class'=>'ext.commands.XCleanCommand',
 *             'webroot'=>'path/to/your/application/webroot'
 *         )
 *     )
 * );
 *
 * Now, under protected directory, you can run commands:
 * yiic clean
 * yiic clean cache
 * yiic clean assets
 * yiic clean runtime
 */
class XCleanCommand extends CConsoleCommand
{
	public $webroot;

	public function getHelp()
	{
		$out = "Clean command allows you to clean up various temporary data Yii and an application are generating.\n\n";
		return $out.parent::getHelp();
	}

	public function actionCache()
	{
		$cache=Yii::app()->getComponent('cache');
		if($cache!==null){
			$cache->flush();
			echo "Done.\n";
		}
		else {
			echo "Please configure cache component.\n";
		}
	}

	public function actionAssets()
	{
		if(empty($this->webroot))
		{
			echo "Please specify a path to webroot in command properties.\n";
			Yii::app()->end();
		}

		$this->cleanDir($this->webroot.'/assets');

		echo "Done.\n";
	}

	public function actionRuntime()
	{
		$this->cleanDir(Yii::app()->getRuntimePath());
		echo "Done.\n";
	}

	private function cleanDir($dir)
	{
		$di = new DirectoryIterator($dir);
		foreach($di as $d)
		{
			if(!$d->isDot())
			{
				echo "Removed ".$d->getPathname()."\n";
				$this->removeDirRecursive($d->getPathname());
			}
		}
	}

	private function removeDirRecursive($dir)
	{
		$files = glob($dir.'*', GLOB_MARK);
		foreach ($files as $file)
		{
			if (is_dir($file))
				$this->removeDirRecursive($file);
			else
				unlink($file);
		}

		if (is_dir($dir))
			rmdir($dir);
	}
}