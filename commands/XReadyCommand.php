<?php
/**
 * XReadyCommand class file.
 *
 * Ready command allows you to set correct folder permissions and image paths of help texts after uploading new version of application.
 *
 * To use this command, first map it in protected/config/console.php as follows:
 * return array(
 *     'commandMap' => array(
 *         'chmode'=>array(
 *             'class'=>'ext.commands.XReadyCommand',
 *             'webroot'=>'path/to/your/application/webroot'
 *             'dataConfig'=>array(
 *                  'tbl_help'=>array('content_et','content_en'),
 *                  'vau.tbl_page_article'=>array('content')
 *              )
 *         )
 *     )
 * );
 *
 * Now, under protected directory, you can run commands:
 * yiic ready file
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XReadyCommand extends CConsoleCommand
{
	public $webroot;
	public $dataConfig;

	public function getHelp()
	{
		$out = "Ready command allows you to clean assets and runtime directories, set correct permissions and image paths after uploading new version of application.\n\n";
		return $out.parent::getHelp();
	}

	public function actionFile()
	{
		if(empty($this->webroot))
		{
			echo "Please specify a path to webroot in command properties.\n";
			Yii::app()->end();
		}

		// clean assets and runtime directory
		$this->cleanDir($this->webroot.'/assets');
		$this->cleanDir(Yii::app()->getRuntimePath());

		// set permission
		@chmod($this->webroot.'/assets',0777);
		@chmod($this->webroot.'/protected/runtime',0777);
		@chmod($this->webroot.'/protected/data',0777);
	}

	public function actionData()
	{
		if(empty($this->dataConfig))
		{
			echo "Please specify a dataConfig property.\n";
			Yii::app()->end();
		}

		foreach ($this->dataConfig as $table=>$columns)
		{
			foreach ($columns as $column)
			{
				$sql="UPDATE {$table} SET {$column}=REPLACE({$column}, '/labs/', '/');";
				Yii::app()->db->createCommand($sql)->execute();
			}
		}
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