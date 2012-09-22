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
 *         )
 *     )
 * );
 *
 * Now, under protected directory, you can run commands:
 * yiic ready folder
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XReadyCommand extends CConsoleCommand
{
	public $webroot;

	public $tblHelp='tbl_help';

	public function getHelp()
	{
		$out = "Ready command allows you to set correct folder permissions and image paths of help texts after uploading new version of application.\n\n";
		return $out.parent::getHelp();
	}

	public function actionFolder()
	{
		@chmod($this->webroot.'/assets',0777);
		@chmod($this->webroot.'/protected/runtime',0777);
		@chmod($this->webroot.'/protected/data',0777);
	}

	public function actionDatabase()
	{
		$sql="UPDATE ".$this->tblHelp." SET content_et=REPLACE(content_et, '/labs/', '/' ), content_en=REPLACE(content_en, '/labs/', '/' );";
		Yii::app()->db->createCommand($sql)->execute();
	}
}