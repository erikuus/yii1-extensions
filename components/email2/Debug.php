<?php
/**
 * Change Log:
 * Replaced css file absolute path with relative path.
 * Added support for dumping multiple flash messages.
 * @author Erik Uus <erik.uus@gmail.com>
 */
class Debug extends CWidget {
	public function run() {
		$flashMessages = Yii::app()->user->getFlashes();
		if ($flashMessages) {
			// register css file
			$cssFile=CHtml::asset(dirname(__FILE__).DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'debug.css');
			Yii::app()->getClientScript()->registerCssFile($cssFile);
			// dump debug info
			foreach($flashMessages as $key=>$message) {
				if(strstr($key,'debug.email'))
					echo $message;
			}
		}
	}
}